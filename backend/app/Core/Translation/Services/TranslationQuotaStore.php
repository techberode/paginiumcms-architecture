<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Translation\Exception\TranslationException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Daily character quota for assisted translation (It.76). 0 = unlimited.
 */
final class TranslationQuotaStore
{
    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private TranslationSettings $settings,
        private string $relativePath = 'data/translation-quota.json',
    ) {
    }

    /**
     * @return array{day: string, used: int, limit: int, remaining: int|null}
     */
    public function snapshot(): array
    {
        $state = $this->readToday();
        $limit = $this->settings->dailyCharLimit();

        return [
            'day' => $state['day'],
            'used' => $state['characters'],
            'limit' => $limit,
            'remaining' => $limit === 0 ? null : max(0, $limit - $state['characters']),
        ];
    }

    public function consume(int $characters): void
    {
        $characters = max(0, $characters);
        if ($characters === 0) {
            return;
        }

        $limit = $this->settings->dailyCharLimit();
        $state = $this->readToday();
        $next = $state['characters'] + $characters;
        if ($limit > 0 && $next > $limit) {
            throw new TranslationException('Daily translation quota exceeded', 429, 'QUOTA_EXCEEDED');
        }

        $this->writer->write($this->relativePath, JsonHelper::encode([
            'day' => $state['day'],
            'characters' => $next,
        ], JSON_PRETTY_PRINT), false);
    }

    /**
     * @return array{day: string, characters: int}
     */
    private function readToday(): array
    {
        $today = gmdate('Y-m-d');
        if (!$this->reader->exists($this->relativePath)) {
            return ['day' => $today, 'characters' => 0];
        }

        try {
            $decoded = JsonHelper::decode($this->reader->read($this->relativePath));
        } catch (\Throwable) {
            return ['day' => $today, 'characters' => 0];
        }

        $day = (string) ($decoded['day'] ?? '');
        if ($day !== $today) {
            return ['day' => $today, 'characters' => 0];
        }

        return [
            'day' => $today,
            'characters' => max(0, (int) ($decoded['characters'] ?? 0)),
        ];
    }
}
