<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodePolicy\Services;

/**
 * HTML/CSS hostile-markup scan shared by ZIP import and Theme Studio (It.88b).
 *
 * SyntaxChecker HTML/CSS stubs stay no-op; this is the fail-closed policy layer.
 */
final class UntrustedMarkupScanner
{
    /**
     * @var list<array{pattern: string, message: string}>
     */
    private const HTML_RULES = [
        ['pattern' => '/<\s*script\b/i', 'message' => 'Forbidden HTML: <script>'],
        ['pattern' => '/javascript\s*:/i', 'message' => 'Forbidden HTML: javascript: URL'],
        ['pattern' => '/\bon[a-z]+\s*=/i', 'message' => 'Forbidden HTML: event handler attribute'],
        ['pattern' => '/<\s*iframe\b/i', 'message' => 'Forbidden HTML: <iframe>'],
        ['pattern' => '/<\s*object\b/i', 'message' => 'Forbidden HTML: <object>'],
        ['pattern' => '/<\s*embed\b/i', 'message' => 'Forbidden HTML: <embed>'],
        ['pattern' => '/<\?php/i', 'message' => 'Forbidden HTML: PHP tag'],
        ['pattern' => '/<\?=/', 'message' => 'Forbidden HTML: PHP short echo'],
        ['pattern' => '/data\s*:\s*text\/html/i', 'message' => 'Forbidden HTML: data:text/html'],
    ];

    /**
     * @var list<array{pattern: string, message: string}>
     */
    private const CSS_RULES = [
        ['pattern' => '/expression\s*\(/i', 'message' => 'Forbidden CSS: expression()'],
        ['pattern' => '/url\s*\(\s*[\'"]?\s*javascript\s*:/i', 'message' => 'Forbidden CSS: url(javascript:)'],
        ['pattern' => '/@import\s+(?:url\s*\(\s*)?[\'"]?\s*https?:/i', 'message' => 'Forbidden CSS: remote @import'],
        ['pattern' => '/behavior\s*:/i', 'message' => 'Forbidden CSS: behavior'],
        ['pattern' => '/-moz-binding\s*:/i', 'message' => 'Forbidden CSS: -moz-binding'],
    ];

    /**
     * @return list<array{line: int, message: string}>
     */
    public function scan(string $relativePath, string $content): array
    {
        $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'html', 'htm' => $this->applyRules($content, self::HTML_RULES),
            'css' => $this->applyRules($content, self::CSS_RULES),
            default => [],
        };
    }

    /**
     * @param list<array{pattern: string, message: string}> $rules
     * @return list<array{line: int, message: string}>
     */
    private function applyRules(string $content, array $rules): array
    {
        $markers = [];
        foreach ($rules as $rule) {
            $matched = preg_match_all($rule['pattern'], $content, $matches, PREG_OFFSET_CAPTURE);
            if ($matched === false || $matched === 0) {
                continue;
            }

            $hits = $matches[0] ?? [];
            foreach ($hits as $hit) {
                $markers[] = [
                    'line' => $this->lineAt($content, $hit[1]),
                    'message' => $rule['message'],
                ];
            }
        }

        usort($markers, static fn (array $a, array $b): int => $a['line'] <=> $b['line']);

        return $markers;
    }

    private function lineAt(string $content, int $offset): int
    {
        if ($offset <= 0) {
            return 1;
        }

        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }
}
