<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use RuntimeException;

/**
 * Cooldown + last-run metadata for newsletter sends (flat-file).
 */
final class NewsletterSendStateStore
{
    private const FILE = 'data/newsletter/send-state.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer
    ) {
    }

    public function lastWeeklyDigestAt(): ?string
    {
        $value = $this->read()['lastWeeklyDigestAt'] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function markWeeklyDigestSent(): void
    {
        $this->mutate(static function (array &$state): void {
            $state['lastWeeklyDigestAt'] = date('c');
        });
    }

    public function isArticleCooldownActive(string $email, int $cooldownHours): bool
    {
        if ($cooldownHours <= 0) {
            return false;
        }

        $normalized = strtolower(trim($email));
        $raw = $this->read()['articleCooldown'][$normalized] ?? null;
        if (!is_string($raw) || $raw === '') {
            return false;
        }

        try {
            $sentAt = new \DateTimeImmutable($raw);
        } catch (\Throwable) {
            return false;
        }

        return $sentAt > new \DateTimeImmutable(sprintf('-%d hours', $cooldownHours));
    }

    public function markArticleSent(string $email): void
    {
        $normalized = strtolower(trim($email));
        $this->mutate(static function (array &$state) use ($normalized): void {
            if (!isset($state['articleCooldown']) || !is_array($state['articleCooldown'])) {
                $state['articleCooldown'] = [];
            }
            $state['articleCooldown'][$normalized] = date('c');
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function read(): array
    {
        if (!$this->reader->exists(self::FILE)) {
            return [];
        }

        try {
            $decoded = json_decode($this->reader->read(self::FILE), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param callable(array<string, mixed>): void $mutator
     */
    private function mutate(callable $mutator): void
    {
        $absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim(self::FILE, '/');
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            $this->writer->createDirectory(dirname(self::FILE));
        }

        $handle = fopen($absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Unable to open newsletter send-state file.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to lock newsletter send-state file.');
            }

            $state = $this->readHandle($handle);
            $mutator($state);
            $this->writeHandle($handle, $state);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @return array<string, mixed>
     */
    private function readHandle($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param resource $handle
     * @param array<string, mixed> $state
     */
    private function writeHandle($handle, array $state): void
    {
        $payload = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '{}';
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }
}
