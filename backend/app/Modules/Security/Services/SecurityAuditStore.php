<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Flat-file security audit log (Iteration 11).
 *
 * Storage: `data/security/audit_events.json`
 */
final class SecurityAuditStore
{
    private const MAX_EVENTS = 2000;

    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private string $storeFile = 'data/security/audit_events.json'
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function append(
        string $type,
        string $severity,
        string $message,
        ?string $userId = null,
        ?string $email = null,
        ?string $ip = null,
        array $metadata = []
    ): void {
        $event = [
            'id' => uniqid('sec_', true),
            'type' => $type,
            'severity' => strtoupper($severity),
            'message' => $message,
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            'metadata' => $metadata,
            'created_at' => date('c'),
        ];

        $this->withLockedStore(function (array &$store) use ($event): void {
            if (!isset($store['events']) || !is_array($store['events'])) {
                $store['events'] = [];
            }

            $store['events'][] = $event;

            if (count($store['events']) > self::MAX_EVENTS) {
                $store['events'] = array_slice($store['events'], -self::MAX_EVENTS);
            }
        });
    }

    /**
     * @param array<string, string> $filters
     * @return list<array<string, mixed>>
     */
    public function list(array $filters = [], int $limit = 100): array
    {
        $store = $this->readStore();
        $events = is_array($store['events'] ?? null) ? $store['events'] : [];

        if ($filters !== []) {
            $events = array_values(array_filter(
                $events,
                static function (mixed $row) use ($filters): bool {
                    if (!is_array($row)) {
                        return false;
                    }

                    foreach ($filters as $key => $value) {
                        if ($value === '') {
                            continue;
                        }

                        if ($key === 'type' && (string) ($row['type'] ?? '') !== $value) {
                            return false;
                        }

                        if ($key === 'severity' && strtoupper((string) ($row['severity'] ?? '')) !== strtoupper($value)) {
                            return false;
                        }

                        if ($key === 'user_id' && (string) ($row['user_id'] ?? '') !== $value) {
                            return false;
                        }
                    }

                    return true;
                }
            ));
        }

        usort(
            $events,
            static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''))
        );

        return array_slice($events, 0, max(1, min(500, $limit)));
    }

    /**
     * @param array<string, string> $filters
     */
    public function exportCsv(array $filters = [], int $limit = 1000): string
    {
        $events = $this->list($filters, $limit);
        $lines = ['id,type,severity,message,user_id,email,ip,created_at'];

        foreach ($events as $event) {
            $lines[] = implode(',', array_map(
                // CSV injection (C11): okrem escapovania `"` musíme odstrániť aj
                // CR/LF, inak by `message`/`email` s newline rozbili riadky CSV.
                static fn (string $value): string => '"' . str_replace('"', '""', LogSanitizer::value($value)) . '"',
                [
                    (string) ($event['id'] ?? ''),
                    (string) ($event['type'] ?? ''),
                    (string) ($event['severity'] ?? ''),
                    (string) ($event['message'] ?? ''),
                    (string) ($event['user_id'] ?? ''),
                    (string) ($event['email'] ?? ''),
                    (string) ($event['ip'] ?? ''),
                    (string) ($event['created_at'] ?? ''),
                ]
            ));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private function readStore(): array
    {
        if (!is_readable($this->absolutePath)) {
            return ['events' => []];
        }

        $raw = file_get_contents($this->absolutePath);
        if ($raw === false || trim($raw) === '') {
            return ['events' => []];
        }

        return $this->normalizeStore(JsonHelper::decode($raw));
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizeStore(array $decoded): array
    {
        $events = $decoded['events'] ?? [];

        return [
            'events' => is_array($events) ? $events : [],
        ];
    }

    /**
     * @param callable(array<string, mixed>&): void $mutator
     */
    private function withLockedStore(callable $mutator): void
    {
        $dir = dirname($this->absolutePath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create security audit directory: ' . $dir);
        }

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Cannot open security audit store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Cannot lock security audit store');
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $store = ['events' => []];
            if ($raw !== false && trim($raw) !== '') {
                $store = $this->normalizeStore(JsonHelper::decode($raw));
            }

            $mutator($store);

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, JsonHelper::encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
