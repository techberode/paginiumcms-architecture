<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Contracts;

interface DiffInterface
{
    public function generate(string $old, string $new): array;
    public function computeDiff(array $old, array $new): array;
}
