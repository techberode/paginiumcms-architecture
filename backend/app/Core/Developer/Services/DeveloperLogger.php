<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer\Services;

/**
 * Perzistentný logger pre Developer Mode (iba keď je brána odomknutá).
 *
 * Zapisuje do storage/logs/developer/YYYY-MM-DD.log – rotácia podľa dňa.
 * Bežné requesty bez odomknutého dev módu tento logger nevolajú.
 */
class DeveloperLogger
{
    private string $logDir;
    /** @var array<int, array<string, mixed>> */
    private array $buffer = [];

    public function __construct(?string $logDir = null)
    {
        $this->logDir = $logDir ?? __DIR__ . '/../../../../storage/logs/developer';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log(string $channel, string $level, string $message, array $context = []): void
    {
        $this->buffer[] = [
            'timestamp' => date('c'),
            'channel' => $channel,
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
        ];

        if (count($this->buffer) >= 20) {
            $this->flush();
        }
    }

    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $file = $this->logDir . '/' . date('Y-m-d') . '.log';
        $lines = '';
        foreach ($this->buffer as $entry) {
            $lines .= json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
        }
        file_put_contents($file, $lines, FILE_APPEND);
        $this->buffer = [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tail(int $limit = 100): array
    {
        $file = $this->logDir . '/' . date('Y-m-d') . '.log';
        if (!file_exists($file)) {
            return [];
        }

        $lines = array_filter(explode("\n", trim((string) file_get_contents($file))));
        $entries = [];
        foreach (array_slice($lines, -$limit) as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $entries[] = $decoded;
            }
        }

        return $entries;
    }

    public function __destruct()
    {
        $this->flush();
    }
}
