<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

use PaginiumCMS\Support\MapEmbedUrlGuard;

/**
 * Built-in public-page widgets (It.93t). Inspired by CoreUI/Konrix cards — original markup, not vendored.
 *
 * Markdown: [widget type="kpi" title="Visitors" value="12.4k" delta="+8%" /]
 */
final class WidgetCatalog
{
    private const TONES = ['primary', 'success', 'warn', 'muted'];

    public function __construct(
        private ?WidgetDefinitionRepository $custom = null,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function catalog(): array
    {
        $builtin = [
            $this->type('kpi', true, [
                'title' => 'string',
                'value' => 'string',
                'delta' => 'string',
                'hint' => 'string',
                'tone' => 'tone',
            ], [
                'title' => 'Visitors',
                'value' => '12.4k',
                'delta' => '+8%',
                'hint' => 'Last 7 days',
                'tone' => 'primary',
            ]),
            $this->type('progress', true, [
                'title' => 'string',
                'label' => 'string',
                'value' => 'percent',
                'tone' => 'tone',
            ], [
                'title' => 'Storage',
                'label' => 'Used',
                'value' => '72',
                'tone' => 'primary',
            ]),
            $this->type('brand', true, [
                'title' => 'string',
                'subtitle' => 'string',
                'cta' => 'string',
                'href' => 'href',
                'tone' => 'tone',
            ], [
                'title' => 'PaginiumCMS',
                'subtitle' => 'Flat-file content engine',
                'cta' => 'Learn more',
                'href' => '/about',
                'tone' => 'primary',
            ]),
            $this->type('quote', true, [
                'quote' => 'string',
                'author' => 'string',
                'role' => 'string',
            ], [
                'quote' => 'The kitchen stays simple — JSON on disk, React at the pass.',
                'author' => 'Alex M.',
                'role' => 'Editor',
            ]),
            $this->type('cta', true, [
                'title' => 'string',
                'subtitle' => 'string',
                'cta' => 'string',
                'href' => 'href',
                'tone' => 'tone',
            ], [
                'title' => 'Ready to publish?',
                'subtitle' => 'Pages, articles, and media without SQL.',
                'cta' => 'Get started',
                'href' => '/contact',
                'tone' => 'primary',
            ]),
            $this->type('timeline', true, [
                'step1' => 'string',
                'step2' => 'string',
                'step3' => 'string',
            ], [
                'step1' => 'Write',
                'step2' => 'Review',
                'step3' => 'Publish',
            ]),
            $this->type('icon-box', true, [
                'badge' => 'string',
                'title' => 'string',
                'body' => 'string',
                'tone' => 'tone',
            ], [
                'badge' => '01',
                'title' => 'No-SQL SSOT',
                'body' => 'Content lives as UTF-8 files on disk.',
                'tone' => 'primary',
            ]),
            $this->type('profile', true, [
                'name' => 'string',
                'role' => 'string',
                'href' => 'href',
            ], [
                'name' => 'Jordan K.',
                'role' => 'Support lead',
                'href' => '/contact',
            ]),
            $this->type('list', true, [
                'title' => 'string',
                'items' => 'string',
            ], [
                'title' => 'Included',
                'items' => 'Pages | Blog | Media library',
            ]),
            $this->type('kpi-row', false, [], []),
            $this->type('map-embed', true, [
                'title' => 'string',
                'src' => 'string',
                'height' => 'string',
            ], [
                'title' => 'Office',
                'src' => '',
                'height' => '320',
            ]),
            $this->type('data-table', true, [
                'title' => 'string',
                'headers' => 'string',
                'rows' => 'string',
            ], [
                'title' => 'Comparison',
                'headers' => 'Plan | Storage | Support',
                'rows' => 'Starter | 5 GB | E-mail | Pro | 50 GB | Priority',
            ]),
            $this->type('bar-chart', true, [
                'title' => 'string',
                'labels' => 'string',
                'values' => 'string',
                'tone' => 'tone',
            ], [
                'title' => 'Weekly traffic',
                'labels' => 'Mon | Tue | Wed | Thu | Fri',
                'values' => '40 | 55 | 48 | 70 | 62',
                'tone' => 'primary',
            ]),
            $this->type('form-cta', true, [
                'title' => 'string',
                'subtitle' => 'string',
                'cta' => 'string',
                'href' => 'href',
                'subject' => 'string',
            ], [
                'title' => 'Need help?',
                'subtitle' => 'Send us a message — we reply within one business day.',
                'cta' => 'Open contact form',
                'href' => '/contact',
                'subject' => '',
            ]),
            $this->type('checklist', true, [
                'title' => 'string',
                'items' => 'string',
                'tone' => 'tone',
            ], [
                'title' => 'Included',
                'items' => 'Flat-file SSOT | Editorial workflow | Media library',
                'tone' => 'success',
            ]),
            $this->type('stat-duo', true, [
                'label1' => 'string',
                'value1' => 'string',
                'label2' => 'string',
                'value2' => 'string',
                'tone' => 'tone',
            ], [
                'label1' => 'Uptime',
                'value1' => '99.9%',
                'label2' => 'Articles',
                'value2' => '240+',
                'tone' => 'primary',
            ]),
        ];

        $custom = $this->custom?->list() ?? [];
        $reserved = array_map(static fn (array $row): string => (string) $row['id'], $builtin);
        foreach ($custom as $entry) {
            $id = (string) ($entry['id'] ?? '');
            if ($id === '' || in_array($id, $reserved, true)) {
                continue;
            }
            $builtin[] = $entry;
        }

        return $builtin;
    }

    /**
     * @return list<string>
     */
    public function builtinIds(): array
    {
        return [
            'kpi',
            'progress',
            'brand',
            'quote',
            'cta',
            'timeline',
            'icon-box',
            'profile',
            'list',
            'kpi-row',
            'map-embed',
            'data-table',
            'bar-chart',
            'form-cta',
            'checklist',
            'stat-duo',
        ];
    }

    public function isKnownType(string $type): bool
    {
        foreach ($this->catalog() as $entry) {
            if (($entry['id'] ?? '') === $type) {
                return true;
            }
        }

        return false;
    }

    public function render(string $rawAttrs, string $inner): string
    {
        $attrs = $this->parseRawAttrs($rawAttrs);
        $type = $this->normalizeType((string) ($attrs['type'] ?? ''));
        if ($type === '' || !$this->isKnownType($type)) {
            if ($inner === '') {
                return '[widget' . $rawAttrs . '/]';
            }

            return '[widget' . $rawAttrs . ']' . $inner . '[/widget]';
        }

        $custom = $this->custom?->get($type);
        if ($custom !== null && !in_array($type, $this->builtinIds(), true)) {
            return $this->renderCustom($custom, $attrs, $inner);
        }

        return match ($type) {
            'kpi' => $this->renderKpi($attrs),
            'progress' => $this->renderProgress($attrs),
            'brand' => $this->renderBrand($attrs),
            'quote' => $this->renderQuote($attrs),
            'cta' => $this->renderCta($attrs),
            'timeline' => $this->renderTimeline($attrs),
            'icon-box' => $this->renderIconBox($attrs),
            'profile' => $this->renderProfile($attrs),
            'list' => $this->renderList($attrs),
            'kpi-row' => '<div class="pg-widget pg-widget-kpis">' . $inner . '</div>',
            'map-embed' => $this->renderMapEmbed($attrs),
            'data-table' => $this->renderDataTable($attrs),
            'bar-chart' => $this->renderBarChart($attrs),
            'form-cta' => $this->renderFormCta($attrs),
            'checklist' => $this->renderChecklist($attrs),
            'stat-duo' => $this->renderStatDuo($attrs),
            default => '[widget' . $rawAttrs . ']' . $inner . ($inner === '' ? '' : '[/widget]'),
        };
    }

    /**
     * @param array<string, string> $fields
     * @param array<string, string> $defaults
     * @return array<string, mixed>
     */
    private function type(string $id, bool $selfClosing, array $fields, array $defaults): array
    {
        $schema = [];
        foreach ($fields as $key => $kind) {
            $field = ['key' => $key, 'kind' => $kind];
            if ($kind === 'tone') {
                $field['options'] = self::TONES;
            }
            $schema[] = $field;
        }

        return [
            'id' => $id,
            'selfClosing' => $selfClosing,
            'fields' => $schema,
            'defaults' => $defaults,
            'source' => 'builtin',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseRawAttrs(string $rawAttrs): array
    {
        $parsed = [];
        if (preg_match_all('/([a-z][a-z0-9_-]*)\s*=\s*"([^"]*)"/', $rawAttrs, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $parsed[$match[1]] = $match[2];
            }
        }

        return $parsed;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type === '') {
            return '';
        }

        if (str_starts_with($type, 'widget-')) {
            $type = substr($type, 7);
        }

        return $type;
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderKpi(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'primary');
        $delta = trim($attrs['delta'] ?? '');
        $deltaHtml = $delta === ''
            ? ''
            : '<span class="pg-widget-delta">' . $this->e($delta) . '</span>';

        return '<div class="pg-widget pg-widget-kpi pg-widget-tone-' . $tone . '">'
            . '<p class="pg-widget-label">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<p class="pg-widget-value">' . $this->e($attrs['value'] ?? '') . $deltaHtml . '</p>'
            . '<p class="pg-widget-hint">' . $this->e($attrs['hint'] ?? '') . '</p>'
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderProgress(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'primary');
        $pct = $this->percent($attrs['value'] ?? '0');

        return '<div class="pg-widget pg-widget-progress pg-widget-tone-' . $tone . '">'
            . '<div class="pg-widget-progress-head">'
            . '<span class="pg-widget-label">' . $this->e($attrs['title'] ?? '') . '</span>'
            . '<span class="pg-widget-value">' . $this->e($attrs['label'] ?? (string) $pct) . '</span>'
            . '</div>'
            . '<div class="pg-widget-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" title="' . $this->e((string) $pct) . '%">'
            . '<span class="pg-widget-bar pg-w-' . $pct . '"></span>'
            . '</div>'
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderBrand(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'primary');
        $href = $this->href($attrs['href'] ?? '/');
        $cta = trim($attrs['cta'] ?? '');
        $ctaHtml = $cta === ''
            ? ''
            : '<a class="pg-widget-link" href="' . $href . '">' . $this->e($cta) . '</a>';

        return '<div class="pg-widget pg-widget-brand pg-widget-tone-' . $tone . '">'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<p class="pg-widget-hint">' . $this->e($attrs['subtitle'] ?? '') . '</p>'
            . $ctaHtml
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderQuote(array $attrs): string
    {
        return '<blockquote class="pg-widget pg-widget-quote">'
            . '<p class="pg-widget-quote-text">' . $this->e($attrs['quote'] ?? '') . '</p>'
            . '<footer class="pg-widget-hint">'
            . '<cite>' . $this->e($attrs['author'] ?? '') . '</cite>'
            . ($this->e($attrs['role'] ?? '') !== '' ? ' · ' . $this->e($attrs['role'] ?? '') : '')
            . '</footer>'
            . '</blockquote>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderCta(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'primary');
        $href = $this->href($attrs['href'] ?? '/contact');

        return '<div class="pg-widget pg-widget-cta pg-widget-tone-' . $tone . '">'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<p class="pg-widget-hint">' . $this->e($attrs['subtitle'] ?? '') . '</p>'
            . '<a class="pg-widget-btn" href="' . $href . '">' . $this->e($attrs['cta'] ?? '') . '</a>'
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderTimeline(array $attrs): string
    {
        $steps = [
            $attrs['step1'] ?? '',
            $attrs['step2'] ?? '',
            $attrs['step3'] ?? '',
        ];
        $items = '';
        foreach ($steps as $index => $step) {
            $items .= '<li class="pg-widget-step"><span class="pg-widget-badge">' . $this->e((string) ($index + 1))
                . '</span><span>' . $this->e($step) . '</span></li>';
        }

        return '<ol class="pg-widget pg-widget-timeline">' . $items . '</ol>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderIconBox(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'primary');
        $badge = trim($attrs['badge'] ?? '');
        $badge = $badge === '' ? '•' : mb_substr($badge, 0, 3);

        return '<div class="pg-widget pg-widget-iconbox pg-widget-tone-' . $tone . '">'
            . '<span class="pg-widget-badge">' . $this->e($badge) . '</span>'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<p class="pg-widget-hint">' . $this->e($attrs['body'] ?? '') . '</p>'
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderProfile(array $attrs): string
    {
        $href = $this->href($attrs['href'] ?? '');
        $name = $this->e($attrs['name'] ?? '');
        $inner = '<p class="pg-widget-title">' . $name . '</p>'
            . '<p class="pg-widget-hint">' . $this->e($attrs['role'] ?? '') . '</p>';

        if ($href !== '#') {
            return '<div class="pg-widget pg-widget-profile"><a class="pg-widget-link" href="' . $href . '">' . $inner . '</a></div>';
        }

        return '<div class="pg-widget pg-widget-profile">' . $inner . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    /**
     * @param array<string, string> $attrs
     */
    private function renderMapEmbed(array $attrs): string
    {
        $src = MapEmbedUrlGuard::sanitizeSrc($attrs['src'] ?? '');
        if ($src === '') {
            return '<div class="pg-widget pg-widget-map pg-widget-empty" role="note"><p class="pg-widget-hint">'
                . $this->e($attrs['title'] ?? 'Map')
                . ' — embed URL missing or not allow-listed (Google Maps /maps/embed only).</p></div>';
        }

        $height = max(200, min(720, (int) ($attrs['height'] ?? 320)));
        $title = $this->e($attrs['title'] ?? 'Map');

        return '<figure class="pg-widget pg-widget-map">'
            . '<figcaption class="pg-widget-label">' . $title . '</figcaption>'
            . '<iframe class="pg-widget-map-frame" title="' . $title . '" src="' . $src . '" loading="lazy" '
            . 'referrerpolicy="no-referrer-when-downgrade" allowfullscreen height="' . $height . '"></iframe>'
            . '</figure>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderDataTable(array $attrs): string
    {
        $headers = $this->splitPipeList($attrs['headers'] ?? '');
        $rowTokens = $this->splitPipeList($attrs['rows'] ?? '');
        $colCount = count($headers);
        if ($colCount === 0) {
            return '<div class="pg-widget pg-widget-table pg-widget-empty"><p class="pg-widget-hint">Table headers required.</p></div>';
        }

        $headHtml = '';
        foreach ($headers as $cell) {
            $headHtml .= '<th scope="col">' . $this->e($cell) . '</th>';
        }

        $bodyHtml = '';
        for ($i = 0; $i < count($rowTokens); $i += $colCount) {
            $bodyHtml .= '<tr>';
            for ($c = 0; $c < $colCount; $c++) {
                $bodyHtml .= '<td>' . $this->e($rowTokens[$i + $c] ?? '') . '</td>';
            }
            $bodyHtml .= '</tr>';
        }

        $title = trim($attrs['title'] ?? '');
        $caption = $title === '' ? '' : '<caption class="pg-widget-label">' . $this->e($title) . '</caption>';

        return '<div class="pg-widget pg-widget-table-wrap">'
            . '<table class="pg-widget-table">' . $caption . '<thead><tr>' . $headHtml . '</tr></thead><tbody>'
            . $bodyHtml . '</tbody></table></div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderBarChart(array $attrs): string
    {
        $labels = $this->splitPipeList($attrs['labels'] ?? '');
        $valuesRaw = $this->splitPipeList($attrs['values'] ?? '');
        $tone = $this->tone($attrs['tone'] ?? 'primary');
        $max = 1;
        $values = [];
        foreach ($valuesRaw as $raw) {
            $n = max(0, (int) round((float) $raw));
            $values[] = $n;
            $max = max($max, $n);
        }

        $bars = '';
        foreach ($labels as $index => $label) {
            $value = $values[$index] ?? 0;
            $pct = (int) round(($value / $max) * 100);
            $bars .= '<div class="pg-widget-chart-row">'
                . '<span class="pg-widget-chart-label">' . $this->e($label) . '</span>'
                . '<div class="pg-widget-chart-track" role="img" aria-label="' . $this->e($label . ' ' . $value) . '">'
                . '<span class="pg-widget-chart-bar pg-w-' . $this->percent((string) $pct) . '"></span>'
                . '</div>'
                . '<span class="pg-widget-chart-value">' . $this->e((string) $value) . '</span>'
                . '</div>';
        }

        return '<div class="pg-widget pg-widget-chart pg-widget-tone-' . $tone . '">'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . $bars
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderFormCta(array $attrs): string
    {
        $path = trim($attrs['href'] ?? '/contact');
        $subject = trim($attrs['subject'] ?? '');
        if ($subject !== '' && $path !== '' && $path !== '#') {
            $sep = str_contains($path, '?') ? '&' : '?';
            $path .= $sep . 'subject=' . rawurlencode($subject);
        }
        $href = $this->href($path);

        return '<div class="pg-widget pg-widget-form-cta">'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<p class="pg-widget-hint">' . $this->e($attrs['subtitle'] ?? '') . '</p>'
            . '<a class="pg-widget-btn" href="' . $href . '">' . $this->e($attrs['cta'] ?? 'Contact') . '</a>'
            . '</div>';
    }

    /**
     * @return list<string>
     */
    private function splitPipeList(string $value): array
    {
        $parts = preg_split('/\s*\|\s*/', $value) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $text = trim($part);
            if ($text !== '') {
                $out[] = $text;
            }
        }

        return $out;
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderList(array $attrs): string
    {
        $parts = preg_split('/\s*\|\s*/', (string) ($attrs['items'] ?? '')) ?: [];
        $lis = '';
        foreach ($parts as $part) {
            $text = trim($part);
            if ($text === '') {
                continue;
            }
            $lis .= '<li>' . $this->e($text) . '</li>';
        }

        return '<div class="pg-widget pg-widget-list">'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<ul>' . $lis . '</ul>'
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderChecklist(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'success');
        $parts = preg_split('/\s*\|\s*/', (string) ($attrs['items'] ?? '')) ?: [];
        $lis = '';
        foreach ($parts as $part) {
            $text = trim($part);
            if ($text === '') {
                continue;
            }
            $lis .= '<li class="pg-widget-check-item">' . $this->e($text) . '</li>';
        }

        return '<div class="pg-widget pg-widget-checklist pg-widget-tone-' . $tone . '">'
            . '<p class="pg-widget-title">' . $this->e($attrs['title'] ?? '') . '</p>'
            . '<ul class="pg-widget-checklist-items">' . $lis . '</ul>'
            . '</div>';
    }

    /**
     * @param array<string, string> $attrs
     */
    private function renderStatDuo(array $attrs): string
    {
        $tone = $this->tone($attrs['tone'] ?? 'primary');

        return '<div class="pg-widget pg-widget-stat-duo pg-widget-tone-' . $tone . '">'
            . '<div class="pg-widget-stat-cell">'
            . '<p class="pg-widget-label">' . $this->e($attrs['label1'] ?? '') . '</p>'
            . '<p class="pg-widget-value">' . $this->e($attrs['value1'] ?? '') . '</p>'
            . '</div>'
            . '<div class="pg-widget-stat-cell">'
            . '<p class="pg-widget-label">' . $this->e($attrs['label2'] ?? '') . '</p>'
            . '<p class="pg-widget-value">' . $this->e($attrs['value2'] ?? '') . '</p>'
            . '</div>'
            . '</div>';
    }

    private function tone(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, self::TONES, true) ? $value : 'primary';
    }

    private function percent(string $value): int
    {
        $n = (int) round((float) $value);
        $n = max(0, min(100, $n));

        return (int) (round($n / 10) * 10);
    }

    private function href(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '#';
        }
        if ($value[0] === '/' || str_starts_with($value, 'https://') || str_starts_with($value, 'http://') || $value === '#') {
            return $this->e($value);
        }

        return '#';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * @param array<string, mixed> $definition
     * @param array<string, string> $attrs
     */
    private function renderCustom(array $definition, array $attrs, string $inner): string
    {
        $template = (string) ($definition['expand'] ?? '');
        if ($template === '') {
            return '';
        }

        $fields = $definition['fields'] ?? [];
        $defaults = is_array($definition['defaults'] ?? null) ? $definition['defaults'] : [];
        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (!is_array($field)) {
                    continue;
                }
                $key = (string) ($field['key'] ?? '');
                $kind = (string) ($field['kind'] ?? 'string');
                if ($key === '') {
                    continue;
                }
                $raw = $attrs[$key] ?? (is_string($defaults[$key] ?? null) ? $defaults[$key] : '');
                $template = str_replace('{{' . $key . '}}', $this->valueForKind($kind, $raw), $template);
            }
        }

        $template = str_replace('{{content}}', $inner, $template);

        return preg_replace('/\{\{[a-z0-9_-]+\}\}/', '', $template) ?? $template;
    }

    private function valueForKind(string $kind, string $raw): string
    {
        return match ($kind) {
            'tone' => $this->tone($raw),
            'percent' => (string) $this->percent($raw),
            'href' => $this->href($raw),
            default => $this->e($raw),
        };
    }
}
