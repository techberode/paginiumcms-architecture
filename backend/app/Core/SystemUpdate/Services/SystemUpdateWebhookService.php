<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\SystemUpdate\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Demo\Services\DemoMode;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Handles GitHub release webhooks for auto-deploy (It.63 v3).
 */
final class SystemUpdateWebhookService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private GitHubReleaseWebhookVerifier $verifier,
        private SystemDeployTriggerService $deployTrigger
    ) {
    }

    /**
     * @return array{
     *     ok: bool,
     *     http_status: int,
     *     error?: string,
     *     ignored?: bool,
     *     reason?: string,
     *     queued?: bool,
     *     queue_id?: string,
     *     ref?: string,
     *     result?: array<string, mixed>|null,
     *     skipped?: bool
     * }
     */
    public function handleRelease(
        string $rawBody,
        string $githubEvent,
        string $signatureHeader,
        ?string $deliveryId = null
    ): array {
        if (DemoMode::isEnabledFromEnv()) {
            return [
                'ok' => false,
                'http_status' => 403,
                'error' => 'System update is disabled on demo instance',
            ];
        }

        $config = $this->settings->group('systemUpdate');
        if (!(bool) ($config['webhookDeployEnabled'] ?? false)) {
            return [
                'ok' => false,
                'http_status' => 403,
                'error' => 'GitHub release webhook deploy is disabled in settings',
            ];
        }

        $secret = trim((string) ($config['githubWebhookSecret'] ?? ''));
        if ($secret === '') {
            return [
                'ok' => false,
                'http_status' => 503,
                'error' => 'GitHub webhook secret is not configured',
            ];
        }

        if (!$this->verifier->verify($rawBody, $signatureHeader, $secret)) {
            return [
                'ok' => false,
                'http_status' => 401,
                'error' => 'Invalid webhook signature',
            ];
        }

        if (strtolower(trim($githubEvent)) !== 'release') {
            return [
                'ok' => true,
                'http_status' => 200,
                'ignored' => true,
                'reason' => 'unsupported_event',
            ];
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return [
                'ok' => false,
                'http_status' => 400,
                'error' => 'Invalid JSON payload',
            ];
        }

        $action = is_string($payload['action'] ?? null) ? $payload['action'] : '';
        if ($action !== 'published') {
            return [
                'ok' => true,
                'http_status' => 200,
                'ignored' => true,
                'reason' => 'action_' . ($action !== '' ? $action : 'unknown'),
            ];
        }

        $release = is_array($payload['release'] ?? null) ? $payload['release'] : [];
        $tag = trim(is_string($release['tag_name'] ?? null) ? $release['tag_name'] : '');
        if ($tag === '') {
            return [
                'ok' => false,
                'http_status' => 422,
                'error' => 'Release tag_name is missing',
            ];
        }

        $auditContext = [
            'source' => 'github_webhook',
            'delivery_id' => LogSanitizer::value($deliveryId ?? ''),
            'action' => $action,
        ];

        return $this->deployTrigger->trigger(
            $tag,
            null,
            'system.deploy.webhook',
            $auditContext
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function publicWebhookConfig(): array
    {
        $config = $this->settings->group('systemUpdate');

        return [
            'path' => '/api/webhooks/github/release',
            'webhook_deploy_enabled' => (bool) ($config['webhookDeployEnabled'] ?? false),
            'secret_configured' => trim((string) ($config['githubWebhookSecret'] ?? '')) !== '',
        ];
    }
}
