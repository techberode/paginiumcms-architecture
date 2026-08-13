<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Seo\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use InvalidArgumentException;
use RuntimeException;

/**
 * Flat-file SSOT for HTTP redirects (It.80a).
 *
 * @phpstan-type RedirectRule array{
 *     id: string,
 *     from: string,
 *     to: string,
 *     status: int,
 *     enabled: bool,
 *     createdAt: string,
 *     updatedAt: string|null,
 *     note: string
 * }
 */
final class RedirectStore
{
    private string $absolutePath;

    /** @var array<string, RedirectRule>|null */
    private ?array $cachedEnabledMap = null;

    private ?int $cachedMtime = null;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/redirects.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listRules(): array
    {
        $rows = [];

        foreach ($this->loadRules() as $rule) {
            $rows[] = $this->toPublicRow($rule);
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) ($a['from'] ?? ''), (string) ($b['from'] ?? '')));

        return $rows;
    }

    /**
     * @return array{to: string, status: int}|null
     */
    public function match(string $requestPath): ?array
    {
        $from = $this->normalizePath($requestPath);
        $map = $this->enabledRulesMap();

        if (!isset($map[$from])) {
            return null;
        }

        $rule = $map[$from];

        return [
            'to' => $rule['to'],
            'status' => $rule['status'],
        ];
    }

    /**
     * @return list<string>
     */
    public function validateAllRules(): array
    {
        $issues = [];
        $rules = $this->loadRules();

        foreach ($rules as $rule) {
            $id = $rule['id'];
            $label = $id !== '' ? $id : $rule['from'];

            try {
                $from = $this->normalizePath($rule['from']);
                $to = $this->normalizePath($rule['to']);
                $this->assertInternalTarget($to);
                $this->assertValidStatus($rule['status']);
                if ($rule['enabled'] === true) {
                    $this->assertNoLoop($from, $to, $id !== '' ? $id : null);
                }
            } catch (InvalidArgumentException $e) {
                $issues[] = sprintf('%s: %s', $label, $e->getMessage());
            }
        }

        return $issues;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $from, string $to, int $status = 301, string $note = ''): array
    {
        $normalizedFrom = $this->normalizePath($from);
        $normalizedTo = $this->normalizePath($to);
        $this->assertInternalTarget($normalizedTo);
        $this->assertValidStatus($status);
        $this->assertNoLoop($normalizedFrom, $normalizedTo);

        $id = 'red_' . bin2hex(random_bytes(8));
        $now = gmdate('c');

        $record = [
            'id' => $id,
            'from' => $normalizedFrom,
            'to' => $normalizedTo,
            'status' => $status,
            'enabled' => true,
            'createdAt' => $now,
            'updatedAt' => null,
            'note' => trim($note),
        ];

        $this->withLockedStore(function (array $store) use ($record, $normalizedFrom): array {
            $rules = $this->rulesFromStore($store);
            if ($this->findRuleByFrom($rules, $normalizedFrom) !== null) {
                throw new InvalidArgumentException('A redirect for this path already exists');
            }
            $rules[$record['id']] = $record;
            $store['schemaVersion'] = 1;
            $store['rules'] = array_values($rules);

            return $store;
        });

        $this->invalidateCache();

        return $this->toPublicRow($record);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, array $payload): array
    {
        $updated = null;

        $this->withLockedStore(function (array $store) use ($id, $payload, &$updated): array {
            $rules = $this->rulesFromStore($store);
            if (!isset($rules[$id])) {
                throw new InvalidArgumentException('Redirect rule not found');
            }

            $current = $rules[$id];
            $from = isset($payload['from']) && is_string($payload['from'])
                ? $this->normalizePath($payload['from'])
                : $current['from'];
            $to = isset($payload['to']) && is_string($payload['to'])
                ? $this->normalizePath($payload['to'])
                : $current['to'];
            $status = isset($payload['status']) && is_numeric($payload['status'])
                ? (int) $payload['status']
                : $current['status'];
            $enabled = isset($payload['enabled']) ? (bool) $payload['enabled'] : $current['enabled'];
            $note = isset($payload['note']) && is_string($payload['note'])
                ? trim($payload['note'])
                : $current['note'];

            $this->assertInternalTarget($to);
            $this->assertValidStatus($status);
            $this->assertNoLoop($from, $to, $id);

            if ($from !== $current['from']) {
                foreach ($rules as $otherId => $other) {
                    if ($otherId !== $id && $other['from'] === $from) {
                        throw new InvalidArgumentException('A redirect for this path already exists');
                    }
                }
            }

            $current['from'] = $from;
            $current['to'] = $to;
            $current['status'] = $status;
            $current['enabled'] = $enabled;
            $current['note'] = $note;
            $current['updatedAt'] = gmdate('c');
            $rules[$id] = $current;
            $store['schemaVersion'] = 1;
            $store['rules'] = array_values($rules);
            $updated = $current;

            return $store;
        });

        $this->invalidateCache();

        if ($updated === null) {
            throw new RuntimeException('Redirect update failed');
        }

        return $this->toPublicRow($updated);
    }

    public function delete(string $id): void
    {
        $this->withLockedStore(function (array $store) use ($id): array {
            $rules = $this->rulesFromStore($store);
            if (!isset($rules[$id])) {
                throw new InvalidArgumentException('Redirect rule not found');
            }
            unset($rules[$id]);
            $store['schemaVersion'] = 1;
            $store['rules'] = array_values($rules);

            return $store;
        });

        $this->invalidateCache();
    }

    public function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return '/';
        }

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        if (str_contains($path, '..') || str_contains($path, "\0") || str_contains($path, '://')) {
            throw new InvalidArgumentException('Invalid path');
        }

        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }

    private function assertInternalTarget(string $path): void
    {
        $this->normalizePath($path);

        if (preg_match('#^/https?://#i', $path) === 1) {
            throw new InvalidArgumentException('External URLs are not allowed');
        }
    }

    private function assertValidStatus(int $status): void
    {
        if (!in_array($status, [301, 302], true)) {
            throw new InvalidArgumentException('Status must be 301 or 302');
        }
    }

    private function assertNoLoop(string $from, string $to, ?string $excludeId = null): void
    {
        if ($from === $to) {
            throw new InvalidArgumentException('Redirect loop: from and to are identical');
        }

        $rules = $this->loadRules();
        $byFrom = [];
        foreach ($rules as $rule) {
            if (!$rule['enabled']) {
                continue;
            }
            if ($excludeId !== null && $rule['id'] === $excludeId) {
                continue;
            }
            $byFrom[$rule['from']] = $rule['to'];
        }

        $byFrom[$from] = $to;

        $visited = [];
        $current = $from;
        for ($depth = 0; $depth < 12; $depth++) {
            if (isset($visited[$current])) {
                throw new InvalidArgumentException('Redirect loop detected');
            }
            $visited[$current] = true;
            $next = $byFrom[$current] ?? null;
            if ($next === null) {
                return;
            }
            $current = $next;
        }

        throw new InvalidArgumentException('Redirect chain too deep');
    }

    /**
     * @return array<string, RedirectRule>
     */
    private function enabledRulesMap(): array
    {
        clearstatcache(true, $this->absolutePath);
        $mtime = is_file($this->absolutePath) ? (int) filemtime($this->absolutePath) : 0;

        if ($this->cachedEnabledMap !== null && $this->cachedMtime === $mtime) {
            return $this->cachedEnabledMap;
        }

        $map = [];
        foreach ($this->loadRules() as $rule) {
            if ($rule['enabled']) {
                $map[$rule['from']] = $rule;
            }
        }

        $this->cachedEnabledMap = $map;
        $this->cachedMtime = $mtime;

        return $map;
    }

    /**
     * @return list<RedirectRule>
     */
    private function loadRules(): array
    {
        if (!is_file($this->absolutePath)) {
            return [];
        }

        $raw = file_get_contents($this->absolutePath);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values($this->rulesFromStore($decoded));
    }

    /**
     * @param array<string, mixed> $store
     * @return array<string, RedirectRule>
     */
    private function rulesFromStore(array $store): array
    {
        $rules = [];
        $items = $store['rules'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $normalized = $this->normalizeRecord($item);
            if ($normalized === null) {
                continue;
            }
            $rules[$normalized['id']] = $normalized;
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $record
     * @return RedirectRule|null
     */
    private function normalizeRecord(array $record): ?array
    {
        if (!isset($record['id'], $record['from'], $record['to'], $record['status'], $record['createdAt'])
            || !is_string($record['id'])
            || !is_string($record['from'])
            || !is_string($record['to'])
            || !is_string($record['createdAt'])
        ) {
            return null;
        }

        try {
            $from = $this->normalizePath($record['from']);
            $to = $this->normalizePath($record['to']);
        } catch (InvalidArgumentException) {
            return null;
        }

        $status = (int) $record['status'];
        if (!in_array($status, [301, 302], true)) {
            return null;
        }

        return [
            'id' => $record['id'],
            'from' => $from,
            'to' => $to,
            'status' => $status,
            'enabled' => (bool) ($record['enabled'] ?? true),
            'createdAt' => $record['createdAt'],
            'updatedAt' => isset($record['updatedAt']) && is_string($record['updatedAt']) ? $record['updatedAt'] : null,
            'note' => isset($record['note']) && is_string($record['note']) ? trim($record['note']) : '',
        ];
    }

    /**
     * @param RedirectRule $rule
     * @return array<string, mixed>
     */
    private function toPublicRow(array $rule): array
    {
        return [
            'id' => $rule['id'],
            'from' => $rule['from'],
            'to' => $rule['to'],
            'status' => $rule['status'],
            'enabled' => $rule['enabled'],
            'createdAt' => $rule['createdAt'],
            'updatedAt' => $rule['updatedAt'],
            'note' => $rule['note'],
        ];
    }

    /**
     * @param array<string, RedirectRule> $rules
     * @return RedirectRule|null
     */
    private function findRuleByFrom(array $rules, string $from): ?array
    {
        foreach ($rules as $rule) {
            if ($rule['from'] === $from) {
                return $rule;
            }
        }

        return null;
    }

    private function invalidateCache(): void
    {
        $this->cachedEnabledMap = null;
        $this->cachedMtime = null;
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $callback
     */
    private function withLockedStore(callable $callback): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create redirect store directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open redirect store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock redirect store');
            }

            $raw = stream_get_contents($handle);
            $store = is_string($raw) && $raw !== ''
                ? (json_decode($raw, true) ?: [])
                : ['schemaVersion' => 1, 'rules' => []];

            if (!is_array($store)) {
                $store = ['schemaVersion' => 1, 'rules' => []];
            }

            $store = $callback($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_UNESCAPED_UNICODE));
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }
}
