<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\AuditTrail\Services;

use PaginiumCMS\Modules\Security\Models\User;

/**
 * Ľudsky čitateľné audit správy (zápis + spätné formátovanie starých logov).
 */
final class AuditMessageFormatter
{
    /** @var array<string, string> */
    private const ACTION_VERBS = [
        'create' => 'vytvoril',
        'update' => 'upravil',
        'delete' => 'zmazal',
        'restore' => 'obnovil',
        'status' => 'zmenil stav',
        'read' => 'zobrazil',
        'login' => 'sa prihlásil',
        'logout' => 'sa odhlásil',
        'backup' => 'zálohoval',
        'restore_backup' => 'obnovil zo zálohy',
    ];

    /** @var array<string, string> */
    private const CONTENT_TYPES = [
        'page' => 'stránku',
        'article' => 'článok',
        'pages' => 'stránku',
        'articles' => 'článok',
    ];

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
        $summary = $context['summary'] ?? null;
        if (is_string($summary) && trim($summary) !== '') {
            return $summary;
        }

        $display = $log['display_message'] ?? null;
        if (is_string($display) && trim($display) !== '') {
            return $display;
        }

        $metadata = is_array($context['metadata'] ?? null) ? $context['metadata'] : [];
        $category = (string) ($context['category'] ?? $this->resolveCategoryFromLog($log));
        $action = strtolower((string) ($context['action'] ?? 'unknown'));
        $target = (string) ($context['target'] ?? 'neznámy cieľ');
        $userContext = is_array($context['user'] ?? null) ? $context['user'] : [];
        $actor = $this->resolveActorName(
            isset($userContext['name']) ? (string) $userContext['name'] : null,
            isset($userContext['email']) ? (string) $userContext['email'] : null
        );

        if (in_array($category, ['content_change', 'content_access'], true)) {
            return $this->formatWithActor($category, $action, $target, $actor, $metadata);
        }

        if ($category !== '' && $category !== 'unknown') {
            return $this->formatWithActor($category, $action, $target, $actor, $metadata);
        }

        $legacyMessage = trim((string) ($log['message'] ?? ''));
        if ($legacyMessage !== '') {
            return $this->humanizeLegacyMessage($legacyMessage, $actor, $metadata);
        }

        return 'Systémová udalosť';
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
            'admin_action' => sprintf(
                '%s vykonal administrátorskú akciu „%s“ na „%s“',
                $actor,
                $normalizedAction,
                $target
            ),
            'security' => sprintf(
                'Bezpečnostná udalosť „%s“ na „%s“ (%s)',
                $normalizedAction,
                $target,
                $actor
            ),
            default => sprintf('%s — %s: „%s“', $actor, strtoupper($normalizedAction), $target),
        };
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function formatContentChange(string $actor, string $action, string $target, array $metadata): string
    {
        $contentType = strtolower((string) ($metadata['content_type'] ?? 'page'));
        $typeLabel = self::CONTENT_TYPES[$contentType] ?? 'obsah';
        $verb = self::ACTION_VERBS[$action] ?? $action;
        $contentLabel = $this->resolveContentLabel($target, $metadata);

        $parts = [sprintf('%s %s %s %s', $actor, $verb, $typeLabel, $contentLabel)];

        $version = $metadata['version'] ?? null;
        if (is_int($version) || (is_string($version) && $version !== '')) {
            $parts[] = sprintf('(verzia %s)', (string) $version);
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
        $contentType = strtolower((string) ($metadata['content_type'] ?? 'obsah'));
        $typeLabel = self::CONTENT_TYPES[$contentType] ?? $contentType;
        $verb = self::ACTION_VERBS[$action] ?? 'pristúpil k';

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
        return match (strtolower(trim($status))) {
            'draft' => 'koncept',
            'published' => 'publikovaný',
            'archived' => 'archivovaný',
            default => $status,
        };
    }

    /**
     * @param array<int|string, mixed> $metadata
     */
    private function resolveDetail(array $metadata): string
    {
        $changeSummary = trim((string) ($metadata['change_summary'] ?? ''));
        if ($changeSummary !== '' && $changeSummary !== 'No changes' && $changeSummary !== 'No significant changes') {
            return $this->translateChangeSummary($changeSummary);
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

    private function translateChangeSummary(string $summary): string
    {
        return str_replace(
            [' added', ' removed', ' modified', 'No changes', 'No significant changes'],
            [' pridaných', ' odstránených', ' upravených', 'Bez zmien', 'Bez významných zmien'],
            $summary
        );
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

        return 'Systém';
    }

    private function quote(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '„(prázdne)“';
        }

        return '„' . $trimmed . '“';
    }
}
