<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Contracts;

interface DiffInterface
{
    /**
     * @return array<int|string, mixed>
     */
    public function generate(string $old, string $new): array;
    /**
     * @param array<int|string, mixed> $old
     * @param array<int|string, mixed> $new
     * @return array<int|string, mixed>
     */
    public function computeDiff(array $old, array $new): array;
}
