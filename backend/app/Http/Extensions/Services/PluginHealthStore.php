<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Extensions\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;
use RuntimeException;

/**
 * Consecutive hook-failure state per plugin (It.89c). Flat-file: data/plugins/health.json.
 *
 * @phpstan-type HealthEntry array{
 *     consecutiveFailures: int,
 *     lastError: string,
 *     lastHook: string,
 *     autoDisabled: bool,
 *     updatedAt: string
 * }
 */
final class PluginHealthStore
{
    private string $absolutePath;

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private string $storeFile = 'data/plugins/health.json',
    ) {
        $this->absolutePath = rtrim($this->reader->getBasePath(), '/')
            . '/' . ltrim($this->storeFile, '/');
    }

    /**
     * @return HealthEntry|null
     */
    public function snapshot(string $pluginId): ?array
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            return null;
        }

        return $this->withLockedStore(function (array $store) use ($pluginId): ?array {
            if (!isset($store[$pluginId]) || !is_array($store[$pluginId])) {
                return null;
            }

            return $this->normalizeEntry($store[$pluginId]);
        });
    }

    public function recordSuccess(string $pluginId): void
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            return;
        }

        $this->withLockedStore(function (array &$store) use ($pluginId): null {
            $entry = $this->normalizeEntry(isset($store[$pluginId]) && is_array($store[$pluginId]) ? $store[$pluginId] : []);
            $entry['consecutiveFailures'] = 0;
            $entry['updatedAt'] = gmdate('c');
            $store[$pluginId] = $entry;

            return null;
        });
    }

    public function recordFailure(string $pluginId, string $hook, string $error): int
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            return 0;
        }

        return $this->withLockedStore(function (array &$store) use ($pluginId, $hook, $error): int {
            $entry = $this->normalizeEntry(isset($store[$pluginId]) && is_array($store[$pluginId]) ? $store[$pluginId] : []);
            $entry['consecutiveFailures'] = $entry['consecutiveFailures'] + 1;
            $entry['lastError'] = $error;
            $entry['lastHook'] = $hook;
            $entry['updatedAt'] = gmdate('c');
            $store[$pluginId] = $entry;

            return $entry['consecutiveFailures'];
        });
    }

    public function markAutoDisabled(string $pluginId): void
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            return;
        }

        $this->withLockedStore(function (array &$store) use ($pluginId): null {
            $entry = $this->normalizeEntry(isset($store[$pluginId]) && is_array($store[$pluginId]) ? $store[$pluginId] : []);
            $entry['autoDisabled'] = true;
            $entry['updatedAt'] = gmdate('c');
            $store[$pluginId] = $entry;

            return null;
        });
    }

    public function clear(string $pluginId): void
    {
        $pluginId = trim($pluginId);
        if ($pluginId === '') {
            return;
        }

        $this->withLockedStore(function (array &$store) use ($pluginId): null {
            unset($store[$pluginId]);

            return null;
        });
    }

    /**
     * @template T
     * @param callable(array<string, mixed>&): T $callback
     * @return T
     */
    private function withLockedStore(callable $callback): mixed
    {
        $this->ensureStorage();

        $handle = fopen($this->absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open plugin health store: ' . $this->absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock plugin health store.');
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

    /**
     * @param resource $handle
     * @return array<string, mixed>
     */
    private function readStore($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = JsonHelper::decode($raw);
        } catch (\JsonException) {
            return [];
        }

        $store = [];
        foreach ($decoded as $id => $entry) {
            if (is_string($id)) {
                $store[$id] = $entry;
            }
        }

        return $store;
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $store
     */
    private function writeStore($handle, array $store): void
    {
        $json = JsonHelper::encode($store, JSON_PRETTY_PRINT);
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $json);
        fflush($handle);
    }

    /**
     * @param array<int|string, mixed> $raw
     * @return HealthEntry
     */
    private function normalizeEntry(array $raw): array
    {
        return [
            'consecutiveFailures' => max(0, (int) ($raw['consecutiveFailures'] ?? 0)),
            'lastError' => (string) ($raw['lastError'] ?? ''),
            'lastHook' => (string) ($raw['lastHook'] ?? ''),
            'autoDisabled' => (bool) ($raw['autoDisabled'] ?? false),
            'updatedAt' => (string) ($raw['updatedAt'] ?? ''),
        ];
    }

    private function ensureStorage(): void
    {
        $dir = dirname($this->storeFile);
        if ($dir !== '' && $dir !== '.') {
            $this->writer->createDirectory($dir);
        }
    }
}
