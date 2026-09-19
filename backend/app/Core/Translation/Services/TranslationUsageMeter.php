<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Translation\Services;

/**
 * Normalized character meter over the It.76 daily quota store (It.77).
 */
final class TranslationUsageMeter
{
    public function __construct(
        private TranslationQuotaStore $quota,
    ) {
    }

    /**
     * @return array{day: string, used: int, limit: int, remaining: int|null}
     */
    public function snapshot(): array
    {
        return $this->quota->snapshot();
    }

    public function consume(int $characters): void
    {
        $this->quota->consume($characters);
    }
}
