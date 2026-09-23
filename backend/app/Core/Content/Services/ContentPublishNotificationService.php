<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\Notification\Services\IncidentNotifier;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\Lang;

/**
 * Admin alerts when content is published (scheduled or manual) via notification connectors.
 * Independent of monitoring.alertsEnabled — uses notifyViaConnectorDetailed like scheduled reports.
 */
final class ContentPublishNotificationService
{
    /** @var list<string> */
    private const SKIPPED_REASONS = [
        'missing_scheduled_at',
        'otp_not_approved',
        'save_failed',
        'archived',
    ];

    public function __construct(
        private SettingsRepositoryInterface $settings,
        private IncidentNotifier $notifier,
        private ContentRepositoryInterface $content,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleScheduledPublishHook(array $context): void
    {
        if (!$this->isMasterEnabled() || !$this->flag('contentPublishOnScheduled', true)) {
            return;
        }

        $type = (string) ($context['type'] ?? '');
        $slug = trim((string) ($context['slug'] ?? ''));
        if ($slug === '' || !$this->isSupportedType($type)) {
            return;
        }

        $title = $this->resolveTitle($type, $slug);
        $scheduledAt = (string) ($context['scheduledAt'] ?? '');

        $subject = Lang::get('publish_notify_scheduled_subject', ['title' => $title], 'content');
        $body = Lang::get('publish_notify_scheduled_body', [
            'type' => $this->typeLabel($type),
            'title' => $title,
            'slug' => $slug,
            'scheduled_at' => $scheduledAt !== '' ? $scheduledAt : '—',
        ], 'content');

        $this->deliver('content.publish.scheduled', $subject, $body, 'info');
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleStatusChangeHook(array $context): void
    {
        if (!$this->isMasterEnabled() || !$this->flag('contentPublishOnManual', false)) {
            return;
        }

        $status = (string) ($context['status'] ?? '');
        $previous = (string) ($context['previousStatus'] ?? '');
        if ($status !== 'published' || $previous === 'published') {
            return;
        }

        $type = (string) ($context['type'] ?? '');
        $slug = trim((string) ($context['slug'] ?? ''));
        if ($slug === '' || !$this->isSupportedType($type)) {
            return;
        }

        $title = $this->resolveTitle($type, $slug);
        $subject = Lang::get('publish_notify_manual_subject', ['title' => $title], 'content');
        $body = Lang::get('publish_notify_manual_body', [
            'type' => $this->typeLabel($type),
            'title' => $title,
            'slug' => $slug,
        ], 'content');

        $this->deliver('content.publish.manual', $subject, $body, 'info');
    }

    /**
     * @param list<array{type?: string, slug?: string, reason?: string}> $skipped
     */
    public function notifySkippedItems(array $skipped): void
    {
        if (!$this->isMasterEnabled() || !$this->flag('contentPublishOnSkipped', false) || $skipped === []) {
            return;
        }

        $lines = [];
        foreach ($skipped as $row) {
            $reason = (string) ($row['reason'] ?? '');
            if (!in_array($reason, self::SKIPPED_REASONS, true)) {
                continue;
            }
            $type = (string) ($row['type'] ?? 'content');
            $slug = trim((string) ($row['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }
            $title = $this->resolveTitle($type, $slug);
            $lines[] = Lang::get('publish_notify_skipped_line', [
                'type' => $this->typeLabel($type),
                'title' => $title,
                'slug' => $slug,
                'reason' => $this->reasonLabel($reason),
            ], 'content');
        }

        if ($lines === []) {
            return;
        }

        $count = count($lines);
        $subject = Lang::get('publish_notify_skipped_subject', ['count' => (string) $count], 'content');
        $body = Lang::get('publish_notify_skipped_body', [
            'lines' => implode("\n", $lines),
        ], 'content');

        $this->deliver('content.publish.skipped', $subject, $body, 'warning');
    }

    private function isMasterEnabled(): bool
    {
        $monitoring = $this->settings->group('monitoring');

        return ($monitoring['contentPublishNotifyEnabled'] ?? false) === true;
    }

    private function flag(string $key, bool $default): bool
    {
        $monitoring = $this->settings->group('monitoring');

        return ($monitoring[$key] ?? $default) === true;
    }

    private function connector(): string
    {
        $monitoring = $this->settings->group('monitoring');
        $connector = (string) ($monitoring['contentPublishConnector'] ?? 'email');
        if ($connector === '') {
            return 'email';
        }

        return $connector;
    }

    private function deliver(string $event, string $subject, string $message, string $severity): void
    {
        $this->notifier->notifyViaConnectorDetailed(
            $this->connector(),
            $event,
            $subject,
            $message,
            $severity
        );
    }

    private function isSupportedType(string $type): bool
    {
        return $type === 'page' || $type === 'article';
    }

    private function resolveTitle(string $type, string $slug): string
    {
        $item = $this->content->findBySlug($slug, $type);
        if ($item !== null) {
            $title = trim($item->getTitle());
            if ($title !== '') {
                return $title;
            }
        }

        return $slug;
    }

    private function typeLabel(string $type): string
    {
        if ($type === 'page') {
            return Lang::get('publish_notify_type_page', [], 'content');
        }
        if ($type === 'article') {
            return Lang::get('publish_notify_type_article', [], 'content');
        }

        return $type;
    }

    private function reasonLabel(string $reason): string
    {
        $key = 'publish_notify_reason_' . $reason;
        $label = Lang::get($key, [], 'content');
        if ($label === $key) {
            return $reason;
        }

        return $label;
    }
}
