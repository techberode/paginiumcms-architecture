<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\AuditTrail\Services;

use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\Lang;

/**
 * Human-readable audit messages (write + re-format legacy logs using current locale).
 */
final class AuditMessageFormatter
{
    /**
     * @param array<int|string, mixed> $metadata
     */
    public function format(
        string $category,
        string $action,
        string $target,
        ?User $user = null,
        array $metadata = []
    ): string {
        return $this->formatWithActor(
            $category,
            $action,
            $target,
            $this->resolveActorName($user?->getName(), $user?->getEmail()),
            $metadata
        );
    }

    /**
     * @param array<int|string, mixed> $log
     */
    public function formatFromLog(array $log): string
    {
        $context = is_array($log['context'] ?? null) ? $log['context'] : [];
        $metadata = is_array($context['metadata'] ?? null) ? $context['metadata'] : [];
        $category = (string) ($context['category'] ?? $this->resolveCategoryFromLog($log));
        $action = strtolower(trim((string) ($context['action'] ?? '')));
        $target = trim((string) ($context['target'] ?? ''));
        $userContext = is_array($context['user'] ?? null) ? $context['user'] : [];
        $actor = $this->resolveActorName(
            isset($userContext['name']) ? (string) $userContext['name'] : null,
            isset($userContext['email']) ? (string) $userContext['email'] : null
        );

        if ($category !== '' && $category !== 'unknown' && $action !== '' && $action !== 'unknown') {
            return $this->formatWithActor(
                $category,
                $action,
                $target !== '' ? $target : $this->t('unknown_target'),
                $actor,
                $metadata
            );
        }

        $display = $log['display_message'] ?? null;
        if (is_string($display) && trim($display) !== '') {
            return $display;
        }

        $summary = $context['summary'] ?? null;
        if (is_string($summary) && trim($summary) !== '') {
            return $summary;
        }

        $legacyMessage = trim((string) ($log['message'] ?? ''));
        if ($legacyMessage !== '') {
            return $this->humanizeLegacyMessage($legacyMessage, $actor, $metadata);
        }

        return $this->t('system_event');
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function formatWithActor(
        string $category,
        string $action,
        string $target,
        string $actor,
        array $metadata = []
    ): string {
        $normalizedAction = strtolower(trim($action));

        return match ($category) {
            'content_change' => $this->formatContentChange($actor, $normalizedAction, $target, $metadata),
            'content_access' => $this->formatContentAccess($actor, $normalizedAction, $target, $metadata),
            'admin_action' => $this->t('admin_action', [
                'actor' => $actor,
                'action' => $normalizedAction,
                'target' => $target,
            ]),
            'security' => $this->t('security', [
                'action' => $normalizedAction,
                'target' => $target,
                'actor' => $actor,
            ]),
            default => $this->t('default', [
                'actor' => $actor,
                'action' => strtoupper($normalizedAction),
                'target' => $target,
            ]),
        };
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function formatContentChange(string $actor, string $action, string $target, array $metadata): string
    {
        $contentType = strtolower((string) ($metadata['content_type'] ?? 'page'));
        $typeLabel = $this->t('content_types.' . $contentType, [], $this->t('content_default_type'));
        $verb = $this->t('actions.' . $action, [], $action);
        $contentLabel = $this->resolveContentLabel($target, $metadata);

        $parts = [sprintf('%s %s %s %s', $actor, $verb, $typeLabel, $contentLabel)];

        $version = $metadata['version'] ?? null;
        if (is_int($version) || (is_string($version) && $version !== '')) {
            $parts[] = $this->t('version', ['version' => (string) $version]);
        }

        if ($action === 'status') {
            $statusLabel = $this->translateStatus((string) ($metadata['content_status'] ?? ''));
            if ($statusLabel !== '') {
                $parts[] = '→ ' . $statusLabel;
            }
        }

        $detail = $this->resolveDetail($metadata);
        if ($detail !== '') {
            $parts[] = '· ' . $detail;
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function formatContentAccess(string $actor, string $action, string $target, array $metadata): string
    {
        $contentType = strtolower((string) ($metadata['content_type'] ?? 'content'));
        $typeLabel = $this->t('content_types.' . $contentType, [], $contentType);
        $verb = $this->t('actions.' . $action, [], $this->t('access_default_verb'));

        return sprintf(
            '%s %s %s %s',
            $actor,
            $verb,
            $typeLabel,
            $this->resolveContentLabel($target, $metadata)
        );
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function resolveContentLabel(string $target, array $metadata): string
    {
        $title = trim((string) ($metadata['content_title'] ?? ''));
        $slug = trim((string) ($metadata['content_slug'] ?? $target));

        if ($title !== '') {
            if ($slug !== '' && strcasecmp($title, $slug) !== 0) {
                return sprintf('„%s“ (%s)', $title, $slug);
            }

            return $this->quote($title);
        }

        return $this->quote($slug !== '' ? $slug : $target);
    }

    private function translateStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        if ($normalized === '') {
            return '';
        }

        $translated = $this->t('status.' . $normalized, [], '');

        return $translated !== 'status.' . $normalized ? $translated : $status;
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function resolveDetail(array $metadata): string
    {
        $changeSummary = $this->formatChangeSummary($metadata);
        if ($changeSummary !== '') {
            return $changeSummary;
        }

        $message = trim((string) ($metadata['message'] ?? ''));
        if ($message === '') {
            return '';
        }

        if (preg_match('/^(Create|Update|Delete|Restore|Status)\s+(page|article):\s+/i', $message) === 1) {
            return '';
        }

        return $message;
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    public function formatChangeSummary(array $metadata): string
    {
        $additions = $metadata['diff_additions'] ?? null;
        $deletions = $metadata['diff_deletions'] ?? null;
        $modifications = $metadata['diff_modifications'] ?? null;

        if ($additions !== null || $deletions !== null || $modifications !== null) {
            return $this->summarizeDiffCounts(
                (int) ($additions ?? 0),
                (int) ($deletions ?? 0),
                (int) ($modifications ?? 0)
            );
        }

        $stored = trim((string) ($metadata['change_summary'] ?? ''));
        if ($stored === '') {
            return '';
        }

        if (in_array($stored, ['No changes', 'No significant changes', 'Bez zmien', 'Bez významných zmien'], true)) {
            return $stored === 'No changes' || $stored === 'Bez zmien'
                ? $this->t('diff.no_changes')
                : $this->t('diff.no_significant');
        }

        if (preg_match_all('/(\d+)\s+(added|removed|modified|pridaných|odstránených|upravených)/i', $stored, $matches, PREG_SET_ORDER) === 0) {
            return $stored;
        }

        $add = 0;
        $del = 0;
        $mod = 0;
        foreach ($matches as $match) {
            $count = (int) $match[1];
            $label = strtolower($match[2]);
            if (str_contains($label, 'add') || str_contains($label, 'pridan')) {
                $add = $count;
            } elseif (str_contains($label, 'remov') || str_contains($label, 'odstr')) {
                $del = $count;
            } elseif (str_contains($label, 'mod') || str_contains($label, 'uprav')) {
                $mod = $count;
            }
        }

        return $this->summarizeDiffCounts($add, $del, $mod);
    }

    public function summarizeDiffCounts(int $additions, int $deletions, int $modifications): string
    {
        if ($additions === 0 && $deletions === 0 && $modifications === 0) {
            return $this->t('diff.no_significant');
        }

        $parts = [];
        if ($additions > 0) {
            $parts[] = $this->t('diff.added', ['count' => (string) $additions]);
        }
        if ($deletions > 0) {
            $parts[] = $this->t('diff.removed', ['count' => (string) $deletions]);
        }
        if ($modifications > 0) {
            $parts[] = $this->t('diff.modified', ['count' => (string) $modifications]);
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<int|string, mixed> $diff
     *
     * @return array{diff_additions: int, diff_deletions: int, diff_modifications: int, change_summary: string}
     */
    public function buildDiffMetadata(?array $diff): array
    {
        $additions = (int) ($diff['additions'] ?? 0);
        $deletions = (int) ($diff['deletions'] ?? 0);
        $modifications = (int) ($diff['modifications'] ?? 0);

        return [
            'diff_additions' => $additions,
            'diff_deletions' => $deletions,
            'diff_modifications' => $modifications,
            'change_summary' => $diff === null
                ? $this->t('diff.no_changes')
                : $this->summarizeDiffCounts($additions, $deletions, $modifications),
        ];
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function humanizeLegacyMessage(string $message, string $actor, array $metadata): string
    {
        if (preg_match(
            '/\[(CONTENT_CHANGE|CONTENT_ACCESS|ADMIN_ACTION|SECURITY)\]\s+(\w+):\s*(.+?)\s+on\s+(.+?)\s+by\s+/',
            $message,
            $matches
        ) === 1) {
            $category = strtolower($matches[1]);
            $action = strtolower($matches[2]);
            $target = trim($matches[3]);

            return $this->formatWithActor($category, $action, $target, $actor, $metadata);
        }

        return $message;
    }

    /**
     * @param array<int|string, mixed> $log
     */
    private function resolveCategoryFromLog(array $log): string
    {
        $category = (string) ($log['category'] ?? '');
        if (str_starts_with($category, 'audit_')) {
            return str_replace('audit_', '', $category);
        }

        $message = (string) ($log['message'] ?? '');
        if (preg_match('/\[(CONTENT_CHANGE|CONTENT_ACCESS|ADMIN_ACTION|SECURITY)\]/', $message, $matches) === 1) {
            return strtolower($matches[1]);
        }

        return 'unknown';
    }

    private function resolveActorName(?string $name, ?string $email): string
    {
        $name = trim((string) $name);
        if ($name !== '') {
            return $name;
        }

        $email = trim((string) $email);
        if ($email !== '') {
            return $email;
        }

        return $this->t('system');
    }

    private function quote(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '„' . $this->t('empty') . '“';
        }

        return '„' . $trimmed . '“';
    }

    /**
     * @param array<string, string> $replace
     */
    private function t(string $key, array $replace = [], string $fallback = ''): string
    {
        $message = Lang::get($key, $replace, 'audit');
        if ($message === $key && $fallback !== '') {
            return $fallback;
        }

        return $message;
    }
}
