<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Analytics\Services\Reporter;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Notification\Services\NotificationFactory;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin notification overview and test-send (Iteration 6).
 */
final class NotificationController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NotificationService $notifications,
        private Reporter $reporter
    ) {
    }

    public function overview(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');

        return $this->json($response, [
            'success' => true,
            'data' => [
                'connectors' => NotificationFactory::connectorOverview($this->settings),
                'active_adapters' => $this->notifications->getAdapters(),
                'fallback_email' => $monitoring['alertEmail'] ?? $general['adminEmail'] ?? '',
                'alerts_enabled' => (bool) ($monitoring['alertsEnabled'] ?? false),
                'analytics' => $this->reporter->getOverview('today'),
                'top_pages' => $this->reporter->getTopPages(5, 'today'),
            ],
        ]);
    }

    public function testSend(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = json_decode((string) $request->getBody(), true);
        if (!is_array($payload)) {
            return $this->json($response, ['success' => false, 'error' => 'Invalid JSON body'], 400);
        }

        $adapter = (string) ($payload['adapter'] ?? '');
        if ($adapter === '') {
            return $this->json($response, ['success' => false, 'error' => 'Adapter is required'], 400);
        }

        if (!in_array($adapter, $this->notifications->getAdapters(), true)) {
            return $this->json($response, ['success' => false, 'error' => 'Adapter is not enabled'], 400);
        }

        $general = $this->settings->group('general');
        $monitoring = $this->settings->group('monitoring');
        $to = (string) ($payload['to'] ?? $monitoring['alertEmail'] ?? $general['adminEmail'] ?? '');

        $ok = $this->notifications->send(
            $adapter,
            $to,
            'PaginiumCMS test notification',
            'This is a test message from PaginiumCMS admin panel.',
            ['event' => 'test', 'severity' => 'info']
        );

        return $this->json($response, [
            'success' => $ok,
            'message' => $ok ? 'Test notification sent' : 'Failed to send test notification',
        ], $ok ? 200 : 502);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
