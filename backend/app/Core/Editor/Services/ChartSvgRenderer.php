<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Editor\Services;

/**
 * Renders validated chart specs to deterministic SVG (It.90e).
 */
final class ChartSvgRenderer
{
    private const MAX_POINTS = 12;

    private const MAX_VALUE = 100000;

    /**
     * @return array{0: ?array<string, mixed>, 1: ?string} Parsed spec or validation error message
     */
    public function parseSpec(string $body): array
    {
        $body = trim($body);
        if ($body === '' || !str_starts_with($body, '{')) {
            return [null, 'Chart block must contain a JSON object.'];
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [null, 'Chart JSON is invalid.'];
        }

        if (!is_array($decoded)) {
            return [null, 'Chart JSON must be an object.'];
        }

        $type = strtolower(trim((string) ($decoded['type'] ?? 'bar')));
        if (!in_array($type, ['bar', 'line'], true)) {
            return [null, 'Chart type must be bar or line.'];
        }

        $labels = $decoded['labels'] ?? null;
        $values = $decoded['values'] ?? null;
        if (!is_array($labels) || !is_array($values)) {
            return [null, 'Chart requires labels and values arrays.'];
        }

        if (count($labels) === 0 || count($labels) > self::MAX_POINTS || count($labels) !== count($values)) {
            return [null, 'Chart labels and values must match (1–12 items).'];
        }

        $parsedLabels = [];
        $parsedValues = [];
        foreach ($labels as $index => $label) {
            $labelText = trim((string) $label);
            if ($labelText === '' || strlen($labelText) > 40) {
                return [null, 'Chart labels must be non-empty (max 40 chars).'];
            }

            if (!is_int($values[$index]) && !is_float($values[$index])) {
                return [null, 'Chart values must be numbers.'];
            }

            $numeric = (float) $values[$index];
            if ($numeric < 0 || $numeric > self::MAX_VALUE) {
                return [null, 'Chart values must be between 0 and ' . self::MAX_VALUE . '.'];
            }

            $parsedLabels[] = $labelText;
            $parsedValues[] = $numeric;
        }

        $title = trim((string) ($decoded['title'] ?? ''));
        if (strlen($title) > 120) {
            return [null, 'Chart title is too long.'];
        }

        return [[
            'type' => $type,
            'title' => $title,
            'labels' => $parsedLabels,
            'values' => $parsedValues,
        ], null];
    }

    /**
     * @param array<string, mixed> $spec
     */
    public function render(array $spec): string
    {
        $type = (string) ($spec['type'] ?? 'bar');
        /** @var list<string> $labels */
        $labels = array_values($spec['labels'] ?? []);
        /** @var list<float> $values */
        $values = array_values($spec['values'] ?? []);
        $title = htmlspecialchars((string) ($spec['title'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $count = count($labels);
        if ($count === 0 || $values === []) {
            return '';
        }

        $max = max($values);
        if ($max <= 0.0) {
            $max = 1.0;
        }

        $width = 640;
        $height = 360;
        $padding = 48;
        $chartHeight = $height - $padding * 2 - ($title !== '' ? 24 : 0);
        $chartWidth = $width - $padding * 2;
        $slot = $chartWidth / $count;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Chart">';
        if ($title !== '') {
            $svg .= '<text x="' . ($width / 2) . '" y="28" text-anchor="middle" font-size="16" font-family="sans-serif" fill="currentColor">' . $title . '</text>';
        }

        $baseY = $height - $padding;

        if ($type === 'line') {
            $points = [];
            for ($i = 0; $i < $count; $i++) {
                $x = $padding + ($slot * $i) + ($slot / 2);
                $y = $baseY - (($values[$i] / $max) * $chartHeight);
                $points[] = round($x, 1) . ',' . round($y, 1);
            }
            $svg .= '<polyline fill="none" stroke="#4f46e5" stroke-width="3" points="' . implode(' ', $points) . '"/>';
            for ($i = 0; $i < $count; $i++) {
                [$x, $y] = explode(',', $points[$i]);
                $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="4" fill="#4f46e5"/>';
            }
        } else {
            $barWidth = max(12.0, ($slot * 0.6));
            for ($i = 0; $i < $count; $i++) {
                $barHeight = ($values[$i] / $max) * $chartHeight;
                $x = $padding + ($slot * $i) + (($slot - $barWidth) / 2);
                $y = $baseY - $barHeight;
                $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($barWidth, 1) . '" height="' . round($barHeight, 1) . '" rx="4" fill="#4f46e5"/>';
            }
        }

        for ($i = 0; $i < $count; $i++) {
            $x = $padding + ($slot * $i) + ($slot / 2);
            $label = htmlspecialchars($labels[$i], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $svg .= '<text x="' . round($x, 1) . '" y="' . ($baseY + 20) . '" text-anchor="middle" font-size="12" font-family="sans-serif" fill="currentColor">' . $label . '</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }
}
