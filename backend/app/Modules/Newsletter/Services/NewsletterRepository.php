<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

final class NewsletterRepository implements NewsletterRepositoryInterface
{
    private const FILE = 'data/newsletter/subscribers.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(string $email, string $source): array
    {
        $normalizedEmail = strtolower(trim($email));
        $normalizedSource = trim($source) !== '' ? trim($source) : 'maintenance';

        return $this->withLockedStore(function (array &$store) use ($normalizedEmail, $normalizedSource): array {
            foreach ($store as $entry) {
                if (strtolower((string) ($entry['email'] ?? '')) === $normalizedEmail) {
                    return [
                        'id' => (string) ($entry['id'] ?? ''),
                        'email' => $normalizedEmail,
                        'subscribedAt' => (string) ($entry['subscribedAt'] ?? date('c')),
                        'source' => (string) ($entry['source'] ?? $normalizedSource),
                        'created' => false,
                    ];
                }
            }

            $record = [
                'id' => uniqid('nl_', true),
                'email' => $normalizedEmail,
                'subscribedAt' => date('c'),
                'source' => $normalizedSource,
            ];
            $store[] = $record;

            return [
                'id' => $record['id'],
                'email' => $record['email'],
                'subscribedAt' => $record['subscribedAt'],
                'source' => $record['source'],
                'created' => true,
            ];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function findAll(): array
    {
        $store = $this->readStore();

        $entries = [];
        foreach ($store as $entry) {
            $entries[] = [
                'id' => (string) ($entry['id'] ?? ''),
                'email' => (string) ($entry['email'] ?? ''),
                'subscribedAt' => (string) ($entry['subscribedAt'] ?? ''),
                'source' => (string) ($entry['source'] ?? ''),
            ];
        }

        usort($entries, static fn (array $a, array $b): int => strcmp($b['subscribedAt'], $a['subscribedAt']));

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    public function countBySource(): array
    {
        $counts = [];
        foreach ($this->findAll() as $entry) {
            $source = $entry['source'];
            $counts[$source] = ($counts[$source] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /**
     * {@inheritDoc}
     */
    public function exportCsv(): string
    {
        $entries = $this->findAll();
        $lines = ['id,email,subscribed_at,source'];

        foreach ($entries as $entry) {
            $lines[] = implode(',', array_map(
                static fn (string $value): string => '"' . str_replace('"', '""', LogSanitizer::value($value)) . '"',
                [
                    $entry['id'],
                    $entry['email'],
                    $entry['subscribedAt'],
                    $entry['source'],
                ]
            ));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @template T
     * @param callable(array<int, array<string, mixed>>): T $mutator
     * @return T
     */
    private function withLockedStore(callable $mutator): mixed
    {
        $absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim(self::FILE, '/');
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            $this->writer->createDirectory(dirname(self::FILE));
        }

        $handle = fopen($absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť súbor newsletteru: ' . $absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok newsletteru.');
            }

            $store = $this->readHandle($handle);
            $before = json_encode($store);
            $result = $mutator($store);
            $after = json_encode($store);

            if ($after !== $before) {
                $this->writeHandle($handle, $store);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readStore(): array
    {
        if (!$this->reader->exists(self::FILE)) {
            return [];
        }

        try {
            $decoded = json_decode($this->reader->read(self::FILE), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param resource $handle
     * @return list<array<string, mixed>>
     */
    private function readHandle($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param resource $handle
     * @param list<array<string, mixed>> $store
     */
    private function writeHandle($handle, array $store): void
    {
        $payload = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '[]';
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }
}
