<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Services;

use PaginiumCMS\Core\Versioning\Contracts\DiffInterface;

class DiffGenerator implements DiffInterface
{
    public function generate(string $old, string $new): array
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);
        $diff = $this->computeDiff($oldLines, $newLines);
        return [
            'lines' => $diff,
            'summary' => $this->getSummary($diff),
            'additions' => $this->countAdditions($diff),
            'deletions' => $this->countDeletions($diff),
        ];
    }

    public function computeDiff(array $old, array $new): array
    {
        $diff = [];
        $i = $j = 0;
        $oldCount = count($old);
        $newCount = count($new);

        while ($i < $oldCount || $j < $newCount) {
            if ($i < $oldCount && $j < $newCount && $old[$i] === $new[$j]) {
                $diff[] = ['type' => 'unchanged', 'old' => $old[$i], 'new' => $new[$j]];
                $i++; $j++;
            } elseif ($j < $newCount && ($i >= $oldCount || !$this->findInArray($old[$i] ?? '', $new, $j + 1))) {
                $diff[] = ['type' => 'added', 'old' => null, 'new' => $new[$j]];
                $j++;
            } elseif ($i < $oldCount && ($j >= $newCount || !$this->findInArray($new[$j] ?? '', $old, $i + 1))) {
                $diff[] = ['type' => 'removed', 'old' => $old[$i], 'new' => null];
                $i++;
            } else {
                $diff[] = ['type' => 'changed', 'old' => $old[$i] ?? '', 'new' => $new[$j] ?? ''];
                $i++; $j++;
            }
        }
        return $diff;
    }

    private function findInArray(string $needle, array $haystack, int $start): bool
    {
        for ($i = $start; $i < count($haystack); $i++) {
            if ($haystack[$i] === $needle) return true;
        }
        return false;
    }

    private function getSummary(array $diff): string
    {
        $additions = $this->countAdditions($diff);
        $deletions = $this->countDeletions($diff);
        if ($additions === 0 && $deletions === 0) return 'Žiadne zmeny';
        return sprintf('%d pridaných, %d odstránených', $additions, $deletions);
    }

    private function countAdditions(array $diff): int { return count(array_filter($diff, fn($d) => $d['type'] === 'added')); }
    private function countDeletions(array $diff): int { return count(array_filter($diff, fn($d) => $d['type'] === 'removed')); }
}
