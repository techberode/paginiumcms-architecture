<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Agent\Services;

use PaginiumCMS\Core\Agent\Exception\AgentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Daily token/run budget as flat-file operational state (It.75).
 */
final class AgentBudgetStore
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private AgentSettings $settings,
        private string $path = 'data/agent/budget.json',
    ) {
    }

    /**
     * @return array{day: string, used: int, limit: int, remaining: int|null}
     */
    public function snapshot(): array
    {
        $state = $this->load();
        $limit = $this->settings->dailyTokenLimit();

        return [
            'day' => $state['day'],
            'used' => $state['used'],
            'limit' => $limit,
            'remaining' => $limit > 0 ? max(0, $limit - $state['used']) : null,
        ];
    }

    public function assertWithinBudget(int $estimatedTokens): void
    {
        $limit = $this->settings->dailyTokenLimit();
        if ($limit <= 0) {
            return;
        }

        $state = $this->load();
        if (($state['used'] + max(0, $estimatedTokens)) > $limit) {
            throw new AgentException('Daily agent token budget exceeded', 429, 'BUDGET');
        }
    }

    public function add(int $tokens): void
    {
        $state = $this->load();
        $state['used'] += max(0, $tokens);
        $this->writer->write($this->path, JsonHelper::encode($state));
    }

    /**
     * @return array{day: string, used: int}
     */
    private function load(): array
    {
        $today = gmdate('Y-m-d');
        if (!$this->reader->exists($this->path)) {
            return ['day' => $today, 'used' => 0];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read($this->path));
        } catch (\Throwable) {
            return ['day' => $today, 'used' => 0];
        }

        $day = is_string($decoded['day'] ?? null) ? $decoded['day'] : $today;
        $used = is_int($decoded['used'] ?? null) ? $decoded['used'] : 0;
        if ($day !== $today) {
            return ['day' => $today, 'used' => 0];
        }

        return ['day' => $day, 'used' => max(0, $used)];
    }
}
