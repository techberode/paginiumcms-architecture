<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Ring buffer of recent firewall incidents (flat-file, flock).
 */
final class FirewallIncidentLogger
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private SettingsRepositoryInterface $settings,
        private string $storeFile = 'data/security/firewall/incidents.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function log(string $ip, string $scenarioId, array $context = []): array
    {
        $entry = [
            'id' => bin2hex(random_bytes(8)),
            'ip' => $ip,
            'scenario' => $scenarioId,
            'uri' => (string) ($context['uri'] ?? ''),
            'user_agent' => (string) ($context['user_agent'] ?? ''),
            'created_at' => gmdate('c'),
        ];

        $this->withLockedStore(function (array &$store) use ($entry): void {
            $items = $store['items'] ?? [];
            if (!is_array($items)) {
                $items = [];
            }

            array_unshift($items, $entry);

            $retention = max(50, (int) ($this->settings->group('firewall')['logRetention'] ?? 500));
            $store['items'] = array_slice($items, 0, $retention);
        });

        return $entry;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(int $limit = 100, int $offset = 0): array
    {
        return $this->withLockedStore(function (array $store) use ($limit, $offset): array {
            $items = $store['items'] ?? [];
            if (!is_array($items)) {
                return [];
            }

            return array_values(array_slice($items, $offset, max(1, $limit)));
        });
    }

    public function countLast24Hours(): int
    {
        $cutoff = time() - 86400;

        return $this->withLockedStore(function (array $store) use ($cutoff): int {
            $items = $store['items'] ?? [];
            if (!is_array($items)) {
                return 0;
            }

            $count = 0;
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $createdAt = strtotime((string) ($item['created_at'] ?? ''));
                if ($createdAt !== false && $createdAt >= $cutoff) {
                    ++$count;
                }
            }

            return $count;
        });
    }

    /**
     * @template T
     * @param callable(array<string, mixed>): T $callback
     * @return T
     */
    private function withLockedStore(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť firewall incidents store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok firewall incidents store.');
            }

            $store = $this->readStore($handle);
            $before = JsonHelper::encode($store);
            $result = $callback($store);
            $after = JsonHelper::encode($store);

            if ($after !== $before) {
                $this->writeStore($handle, $store);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!file_exists($this->absolutePath)) {
            file_put_contents($this->absolutePath, JsonHelper::encode(['items' => []], JSON_PRETTY_PRINT));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readStore(mixed $handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return ['items' => []];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : ['items' => []];
    }

    /**
     * @param array<string, mixed> $store
     */
    private function writeStore(mixed $handle, array $store): void
    {
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, JsonHelper::encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($handle);
    }
}
