<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\I18n\Services;

use InvalidArgumentException;
use JsonException;

/**
 * Extracts MessageTree objects from translation catalog source files.
 */
final class TranslationMessageTreeParser
{
    /**
     * @return array<string, mixed>
     */
    public function parseTypeScriptCatalog(string $content): array
    {
        if (!preg_match('/MessageTree\s*=\s*\{/s', $content, $match, PREG_OFFSET_CAPTURE)) {
            throw new InvalidArgumentException('Missing MessageTree export object');
        }

        $exportPos = (int) $match[0][1];
        $braceStart = strpos($content, '{', $exportPos);
        if ($braceStart === false) {
            throw new InvalidArgumentException('Missing object literal');
        }

        $objectLiteral = $this->extractBalancedSubstring($content, $braceStart, '{', '}');
        $json = $this->normalizeObjectLiteralToJson($objectLiteral);

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException('Catalog object is not valid JSON: ' . $e->getMessage(), 0, $e);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException('Catalog root must be an object');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function extractBalancedSubstring(string $content, int $start, string $open, string $close): string
    {
        $depth = 0;
        $length = strlen($content);
        $inString = false;
        $stringChar = '';
        $escaped = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $content[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }

                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === $stringChar) {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $inString = true;
                $stringChar = $char;
                continue;
            }

            if ($char === $open) {
                $depth++;
            } elseif ($char === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }

        throw new InvalidArgumentException('Unbalanced braces in catalog object');
    }

    private function normalizeObjectLiteralToJson(string $objectLiteral): string
    {
        $withoutTrailingCommas = preg_replace('/,\s*([}\]])/', '$1', $objectLiteral);
        if (!is_string($withoutTrailingCommas)) {
            throw new InvalidArgumentException('Failed to normalize catalog object');
        }

        return $withoutTrailingCommas;
    }
}
