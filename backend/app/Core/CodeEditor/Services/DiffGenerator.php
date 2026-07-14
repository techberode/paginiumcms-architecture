<?php
// backend/app/Core/CodeEditor/Services/DiffGenerator.php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

class DiffGenerator
{
    private Differ $differ;

    public function __construct()
    {
        $this->differ = new Differ(new UnifiedDiffOutputBuilder(
            "--- Original\n+++ New\n",
            false
        ));
    }

    /**
     * Generuje podrobný diff medzi dvoma verziami
     */
    public function generateDetailedDiff(
        string $oldContent,
        string $newContent,
        string $oldFrontMatter = '',
        string $newFrontMatter = ''
    ): array {
        $diff = [
            'content' => $this->generateDiff($oldContent, $newContent),
            'front_matter' => $this->generateDiff($oldFrontMatter, $newFrontMatter),
            'additions' => 0,
            'deletions' => 0,
            'modifications' => 0,
            'lines' => []
        ];

        // Výpočet štatistík
        $oldLines = explode("\n", $oldContent);
        $newLines = explode("\n", $newContent);
        
        $diffResult = $this->computeLineDiff($oldLines, $newLines);
        $diff['lines'] = $diffResult['lines'];
        $diff['additions'] = $diffResult['additions'];
        $diff['deletions'] = $diffResult['deletions'];
        $diff['modifications'] = $diffResult['modifications'];

        return $diff;
    }

    /**
     * Generuje štandardný diff reťazec
     */
    public function generateDiff(string $old, string $new): string
    {
        if ($old === $new) {
            return '';
        }

        return $this->differ->diff($old, $new);
    }

    /**
     * Porovná dva reťazce riadok po riadku
     */
    private function computeLineDiff(array $oldLines, array $newLines): array
    {
        $result = [
            'lines' => [],
            'additions' => 0,
            'deletions' => 0,
            'modifications' => 0
        ];

        $i = $j = 0;
        $oldCount = count($oldLines);
        $newCount = count($newLines);

        while ($i < $oldCount || $j < $newCount) {
            if ($i < $oldCount && $j < $newCount && $oldLines[$i] === $newLines[$j]) {
                $result['lines'][] = [
                    'type' => 'unchanged',
                    'old_line' => $i + 1,
                    'new_line' => $j + 1,
                    'content' => $oldLines[$i]
                ];
                $i++;
                $j++;
            } elseif ($j < $newCount && ($i >= $oldCount || !$this->findInArray($oldLines[$i] ?? '', $newLines, $j + 1))) {
                $result['lines'][] = [
                    'type' => 'added',
                    'old_line' => null,
                    'new_line' => $j + 1,
                    'content' => $newLines[$j]
                ];
                $result['additions']++;
                $j++;
            } elseif ($i < $oldCount && ($j >= $newCount || !$this->findInArray($newLines[$j] ?? '', $oldLines, $i + 1))) {
                $result['lines'][] = [
                    'type' => 'removed',
                    'old_line' => $i + 1,
                    'new_line' => null,
                    'content' => $oldLines[$i]
                ];
                $result['deletions']++;
                $i++;
            } else {
                // Zmenený riadok
                $result['lines'][] = [
                    'type' => 'modified',
                    'old_line' => $i + 1,
                    'new_line' => $j + 1,
                    'old_content' => $oldLines[$i] ?? '',
                    'new_content' => $newLines[$j] ?? ''
                ];
                $result['modifications']++;
                $i++;
                $j++;
            }
        }

        return $result;
    }

    private function findInArray(string $needle, array $haystack, int $start): bool
    {
        for ($i = $start; $i < count($haystack); $i++) {
            if ($haystack[$i] === $needle) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generuje HTML diff pre zobrazenie
     */
    public function generateHtmlDiff(string $old, string $new): string
    {
        $diff = $this->generateDiff($old, $new);
        $lines = explode("\n", $diff);
        $html = '<div class="diff-container"><pre>';

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            
            $firstChar = $line[0] ?? '';
            $class = '';
            $content = htmlspecialchars(substr($line, 1));
            
            if ($firstChar === '+') {
                $class = 'diff-added';
            } elseif ($firstChar === '-') {
                $class = 'diff-removed';
            } elseif ($firstChar === '@') {
                $class = 'diff-header';
            }
            
            $html .= sprintf('<div class="%s">%s%s</div>', $class, $firstChar, $content);
        }

        $html .= '</pre></div>';
        return $html;
    }
}
