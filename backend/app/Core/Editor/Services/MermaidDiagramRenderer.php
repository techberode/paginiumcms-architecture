<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

use Atelier\Diagram\Diagram;
use Atelier\Diagram\Exception\ParseException;

/**
 * Normalizes common Mermaid syntax and renders deterministic SVG via atelier/diagram (It.90c).
 */
final class MermaidDiagramRenderer
{
    public const MAX_SOURCE_LENGTH = 16384;

    public function __construct(
        private MermaidSvgSanitizer $svgSanitizer = new MermaidSvgSanitizer()
    ) {
    }

    public function renderSvg(string $source): ?string
    {
        $normalized = $this->normalizeSource($source);
        if ($normalized === '') {
            return null;
        }

        try {
            $svg = Diagram::fromMermaid($normalized)->toSvg();
        } catch (ParseException) {
            return null;
        }

        $safe = $this->svgSanitizer->sanitize($svg);

        return $safe !== '' ? $safe : null;
    }

    public function validateSource(string $source): ?string
    {
        $trimmed = trim($source);
        if ($trimmed === '') {
            return 'Mermaid diagram cannot be empty.';
        }

        if (strlen($trimmed) > self::MAX_SOURCE_LENGTH) {
            return 'Mermaid diagram is too long.';
        }

        $lower = strtolower($trimmed);
        if (str_contains($lower, '<script') || str_contains($lower, '<iframe') || preg_match('/<[a-z][^>]*>/i', $trimmed) === 1) {
            return 'Mermaid blocks must not contain HTML tags.';
        }

        if ($this->renderSvg($trimmed) === null) {
            return 'Mermaid diagram syntax is invalid or unsupported.';
        }

        return null;
    }

    public function normalizeSource(string $source): string
    {
        $source = trim(str_replace("\r", '', $source));
        if ($source === '') {
            return '';
        }

        $source = preg_replace('/^graph\s+(TD|LR|TB|RL|BT)/im', 'flowchart $1', $source) ?? $source;

        if (preg_match('/^(flowchart\s+(?:TD|LR|TB|RL|BT))\s*;\s*(.+)$/is', $source, $matches) === 1) {
            $lines = [trim($matches[1])];
            foreach (preg_split('/\s*;\s*/', trim($matches[2])) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $lines[] = $part;
                }
            }

            return implode("\n", $lines);
        }

        $lines = preg_split('/\n/', $source) ?: [];
        $output = [];
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if ($index === 0) {
                $header = preg_replace('/\s*;.*$/', '', $line) ?? $line;
                $output[] = $header;
                $rest = preg_replace('/^[^;]+;\s*/', '', $line) ?? $line;
                if ($rest !== $line && trim($rest) !== '') {
                    foreach (preg_split('/\s*;\s*/', trim($rest)) ?: [] as $part) {
                        $part = trim($part);
                        if ($part !== '') {
                            $output[] = $part;
                        }
                    }
                }
                continue;
            }

            foreach (preg_split('/\s*;\s*/', $line) ?: [] as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $output[] = $part;
                }
            }
        }

        return implode("\n", $output);
    }
}
