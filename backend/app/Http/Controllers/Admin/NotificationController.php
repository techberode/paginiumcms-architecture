<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Analytics\Services\Reporter;
use PaginiumCMS\Core\Monitoring\Services\MonitoringReportScheduler;
use PaginiumCMS\Core\Monitoring\Services\MonitoringScheduler;
use PaginiumCMS\Core\Monitoring\Services\SchedulerStateStore;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\NotificationFactory;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin notification overview, scheduled reports and test-send (Iteration 6–7).
 */
final class NotificationController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NotificationService $notifications,
        private Reporter $reporter,
        private MonitoringReportScheduler $reportScheduler,
        private MonitoringScheduler $monitoringScheduler,
        private SchedulerStateStore $state,
        private JsonResponder $json
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');

        return $this->json->success($response, [
            'connectors' => NotificationFactory::connectorOverview($this->settings),
            'active_adapters' => $this->notifications->getAdapters(),
            'fallback_email' => $this->resolveRecipientEmail([], $monitoring, $general, $this->settings->group('smtp')),
            'alerts_enabled' => (bool) ($monitoring['alertsEnabled'] ?? false),
            'analytics' => $this->reporter->getOverview('today'),
            'top_pages' => $this->reporter->getTopPages(5, 'today'),
            'schedule' => $this->reportScheduler->schedulePreview(),
            'log_incidents' => [
                'notify_errors' => (bool) ($monitoring['notifyLogErrors'] ?? true),
                'notify_warnings' => (bool) ($monitoring['notifyLogWarnings'] ?? false),
                'connector' => (string) ($monitoring['logIncidentConnector'] ?? 'all'),
            ],
        ]);
    }

    public function schedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $monitoring = $this->settings->group('monitoring');

        return $this->json->success($response, [
            'schedule' => $this->reportScheduler->schedulePreview(),
            'log_incidents' => [
                'notify_errors' => (bool) ($monitoring['notifyLogErrors'] ?? true),
                'notify_warnings' => (bool) ($monitoring['notifyLogWarnings'] ?? false),
                'connector' => (string) ($monitoring['logIncidentConnector'] ?? 'all'),
            ],
            'state' => $this->state->snapshot(),
        ]);
    }

    public function sendReport(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        $force = is_array($payload) && (bool) ($payload['force'] ?? false);

        try {
            $result = $this->reportScheduler->runIfDue($force);
        } catch (\Throwable $e) {
            return $this->json->respond($response, [
                'success' => false,
                'message' => 'Failed to send monitoring report: ' . $e->getMessage(),
                'result' => ['sent' => false, 'reason' => 'internal_error'],
            ], 422);
        }

        $sent = $result['sent'];
        $message = $sent ? 'Monitoring report sent' : $this->reportFailureMessage($result);

        return $this->json->respond($response, [
            'success' => $sent,
            'message' => $message,
            'result' => $result,
        ], $sent ? 200 : 422);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function reportFailureMessage(array $result): string
    {
        return match ((string) ($result['reason'] ?? '')) {
            'delivery_failed' => 'Connector failed to deliver the report. Check SMTP credentials and server reachability.',
            'connector_inactive' => 'Selected report connector is not enabled. Enable it under Settings → Connectors.',
            'no_connectors' => 'No notification connectors are enabled.',
            'missing_recipient' => 'Set Monitoring → alert email or General → admin email.',
            'disabled' => 'Scheduled reports are disabled in Settings → Monitoring.',
            'not_due' => 'Report is not due yet.',
            default => 'Failed to send monitoring report.',
        };
    }

    public function runSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->monitoringScheduler->runIfDue();

        return $this->json->success($response, $result);
    }

    public function testSend(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $adapter = (string) ($payload['adapter'] ?? '');
        if ($adapter === '') {
            return $this->json->error($response, 'Adapter is required', 400);
        }

        if (!in_array($adapter, $this->notifications->getAdapters(), true)) {
            return $this->json->error($response, 'Adapter is not enabled', 400);
        }

        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');
        $smtp = $this->settings->group('smtp');
        $to = $this->resolveRecipientEmail($payload, $monitoring, $general, $smtp);

        if ($to === '') {
            return $this->json->respond($response, [
                'success' => false,
                'message' => 'Set recipient: Monitoring → alert email, General → admin email, or pass "to" in the request.',
            ], 422);
        }

        $ok = $this->notifications->send(
            $adapter,
            $to,
            'PaginiumCMS test notification',
            'This is a test message from PaginiumCMS admin panel.',
            ['event' => 'test', 'severity' => 'info']
        );

        return $this->json->respond($response, [
            'success' => $ok,
            'message' => $ok ? 'Test notification sent' : 'Failed to send test notification',
        ], $ok ? 200 : 502);
    }

    public function testConnector(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $connector = (string) ($payload['connector'] ?? '');
        if ($connector === '') {
            return $this->json->error($response, 'Connector is required', 400);
        }

        $allowed = ['email', 'ntfy', 'discord', 'telegram', 'webhook'];
        if (!in_array($connector, $allowed, true)) {
            return $this->json->error($response, 'Unknown connector', 400);
        }

        $smtp = $this->settings->group('smtp');
        $connectors = $this->settings->group('connectors');
        $authError = NotificationFactory::connectorAuthError($connector, $connectors, $smtp);
        if ($authError !== null) {
            return $this->json->respond($response, [
                'success' => false,
                'message' => $authError,
                'authenticated' => false,
            ], 422);
        }

        if (!in_array($connector, $this->notifications->getAdapters(), true)) {
            return $this->json->respond($response, [
                'success' => false,
                'message' => 'Connector is configured but not active — save settings and retry.',
                'authenticated' => true,
            ], 422);
        }

        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');
        $smtp = $this->settings->group('smtp');
        $to = $this->resolveRecipientEmail($payload, $monitoring, $general, $smtp);

        if ($to === '') {
            return $this->json->respond($response, [
                'success' => false,
                'message' => 'Set recipient: Monitoring → alert email, General → admin email, or pass "to" in the request.',
            ], 422);
        }

        $ok = $this->notifications->send(
            $connector,
            $to,
            'PaginiumCMS connector test',
            'Connector authentication and delivery test from PaginiumCMS admin.',
            ['event' => 'connector_test', 'severity' => 'info']
        );

        return $this->json->respond($response, [
            'success' => $ok,
            'message' => $ok ? 'Connector test succeeded' : 'Connector test failed — check server URL, topic, and credentials.',
            'authenticated' => true,
        ], $ok ? 200 : 502);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $monitoring
     * @param array<string, mixed> $general
     * @param array<string, mixed> $smtp
     */
    private function resolveRecipientEmail(array $payload, array $monitoring, array $general, array $smtp): string
    {
        foreach ([
            $payload['to'] ?? null,
            $monitoring['alertEmail'] ?? null,
            $general['adminEmail'] ?? null,
            $smtp['fromEmail'] ?? null,
        ] as $candidate) {
            $email = trim((string) $candidate);
            if ($email !== '') {
                return $email;
            }
        }

        return '';
    }
}
