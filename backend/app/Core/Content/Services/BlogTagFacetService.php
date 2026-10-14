<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Collapses noisy article tags into public blog facets (auto rules + optional manual synonyms).
 */
final class BlogTagFacetService
{
    private const MANUAL_SYNONYMS_MAX_BYTES = 65536;

    public function __construct(
        private SettingsRepositoryInterface $settings,
    ) {
    }

    public function isEnabled(): bool
    {
        $content = $this->settings->group('content');

        return ($content['blogTagFacetsEnabled'] ?? true) !== false;
    }

    public function maxVisible(): int
    {
        $content = $this->settings->group('content');
        $value = (int) ($content['blogSidebarTagLimit'] ?? 28);

        return max(8, min(80, $value));
    }

    /**
     * @param list<string> $rawTags
     * @param array<string, int> $usageCounts tag => article count
     * @return list<array{key: string, label: string, count: int, aliases: list<string>}>
     */
    public function buildFacets(array $rawTags, array $usageCounts): array
    {
        if (!$this->isEnabled() || $rawTags === []) {
            return $this->facetsFromRawList($rawTags, $usageCounts);
        }

        $manual = $this->manualSynonymGroups();
        $groups = [];

        foreach ($rawTags as $raw) {
            $raw = trim($raw);
            if ($raw === '') {
                continue;
            }

            $groupKey = $this->resolveGroupKey($raw, $manual);
            if ($groupKey === '') {
                continue;
            }

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'rawTags' => [],
                    'labelOverride' => $manual['labels'][$groupKey] ?? null,
                ];
            }

            $groups[$groupKey]['rawTags'][$raw] = true;
        }

        $facets = [];
        foreach ($groups as $groupKey => $group) {
            $aliases = array_keys($group['rawTags']);
            sort($aliases, SORT_NATURAL | SORT_FLAG_CASE);

            $count = 0;
            foreach ($aliases as $alias) {
                $count += $usageCounts[$alias] ?? 0;
            }

            $label = is_string($group['labelOverride']) && $group['labelOverride'] !== ''
                ? $group['labelOverride']
                : $this->pickDisplayLabel($aliases, $usageCounts);

            $facets[] = [
                'key' => $this->facetUrlKey($groupKey, $label),
                'label' => $label,
                'count' => $count,
                'aliases' => $aliases,
            ];
        }

        usort($facets, static function (array $a, array $b): int {
            if ($a['count'] !== $b['count']) {
                return $b['count'] <=> $a['count'];
            }

            return strnatcasecmp($a['label'], $b['label']);
        });

        return $facets;
    }

    /**
     * @param list<string> $allRawTags
     * @return list<string> lowercase needles for index filter
     */
    public function expandFilterNeedles(string $filterParam, array $allRawTags): array
    {
        $param = trim($filterParam);
        if ($param === '') {
            return [];
        }

        if (!$this->isEnabled()) {
            return [mb_strtolower($param)];
        }

        $usage = [];
        foreach ($allRawTags as $tag) {
            $usage[$tag] = 1;
        }

        $facets = $this->buildFacets($allRawTags, $usage);
        $paramLower = mb_strtolower($param);

        foreach ($facets as $facet) {
            if ($facet['key'] === $param || mb_strtolower($facet['key']) === $paramLower) {
                return $this->lowercaseAliases($facet['aliases']);
            }

            foreach ($facet['aliases'] as $alias) {
                if (mb_strtolower($alias) === $paramLower) {
                    return $this->lowercaseAliases($facet['aliases']);
                }
            }
        }

        return [$paramLower];
    }

    /**
     * Automatic normalization key (before manual synonym merge).
     */
    public function automaticGroupKey(string $tag): string
    {
        $text = trim($tag);
        if ($text === '') {
            return '';
        }

        if (str_contains($text, ':')) {
            $text = explode(':', $text, 2)[0];
        }

        $text = preg_replace('/\d+$/', '', $text) ?? $text;
        $text = $this->foldAscii($text);
        $text = mb_strtolower($text);

        return $text;
    }

    /**
     * @return array{labels: array<string, string>, aliasToGroup: array<string, string>}
     */
    private function manualSynonymGroups(): array
    {
        $content = $this->settings->group('content');
        $raw = $content['blogTagFacetSynonyms'] ?? '';
        if (!is_string($raw) || trim($raw) === '') {
            return ['labels' => [], 'aliasToGroup' => []];
        }

        if (strlen($raw) > self::MANUAL_SYNONYMS_MAX_BYTES) {
            return ['labels' => [], 'aliasToGroup' => []];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['labels' => [], 'aliasToGroup' => []];
        }

        if (!is_array($decoded)) {
            return ['labels' => [], 'aliasToGroup' => []];
        }

        $labels = [];
        $aliasToGroup = [];

        foreach ($decoded as $canonical => $aliases) {
            if (!is_string($canonical) || trim($canonical) === '') {
                continue;
            }

            $groupKey = $this->automaticGroupKey($canonical);
            if ($groupKey === '') {
                $groupKey = mb_strtolower(trim($canonical));
            }

            $labels[$groupKey] = trim($canonical);
            $aliasToGroup[mb_strtolower(trim($canonical))] = $groupKey;

            if (!is_array($aliases)) {
                continue;
            }

            foreach ($aliases as $alias) {
                if (!is_string($alias) || trim($alias) === '') {
                    continue;
                }
                $aliasToGroup[mb_strtolower(trim($alias))] = $groupKey;
            }
        }

        return ['labels' => $labels, 'aliasToGroup' => $aliasToGroup];
    }

    /**
     * @param array{labels: array<string, string>, aliasToGroup: array<string, string>} $manual
     */
    private function resolveGroupKey(string $rawTag, array $manual): string
    {
        $lower = mb_strtolower($rawTag);
        if (isset($manual['aliasToGroup'][$lower])) {
            return $manual['aliasToGroup'][$lower];
        }

        return $this->automaticGroupKey($rawTag);
    }

    /**
     * @param list<string> $rawTags
     * @param array<string, int> $usageCounts
     * @return list<array{key: string, label: string, count: int, aliases: list<string>}>
     */
    private function facetsFromRawList(array $rawTags, array $usageCounts): array
    {
        $facets = [];
        foreach ($rawTags as $tag) {
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }

            $facets[] = [
                'key' => $tag,
                'label' => $tag,
                'count' => $usageCounts[$tag] ?? 0,
                'aliases' => [$tag],
            ];
        }

        return $facets;
    }

    /**
     * @param list<string> $aliases
     * @param array<string, int> $usageCounts
     */
    private function pickDisplayLabel(array $aliases, array $usageCounts): string
    {
        usort($aliases, static function (string $a, string $b) use ($usageCounts): int {
            $countA = $usageCounts[$a] ?? 0;
            $countB = $usageCounts[$b] ?? 0;
            if ($countA !== $countB) {
                return $countB <=> $countA;
            }

            $lenA = mb_strlen($a);
            $lenB = mb_strlen($b);
            if ($lenA !== $lenB) {
                return $lenA <=> $lenB;
            }

            return strnatcasecmp($a, $b);
        });

        return $aliases[0] ?? '';
    }

    private function facetUrlKey(string $groupKey, string $label): string
    {
        if ($groupKey !== '') {
            return $groupKey;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', $this->foldAscii(mb_strtolower($label))) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : mb_strtolower($label);
    }

    private function foldAscii(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_D);
            if (is_string($normalized)) {
                $stripped = preg_replace('/\p{M}/u', '', $normalized);

                return is_string($stripped) ? $stripped : $text;
            }
        }

        return $text;
    }

    /**
     * @param list<string> $aliases
     * @return list<string>
     */
    private function lowercaseAliases(array $aliases): array
    {
        $needles = [];
        foreach ($aliases as $alias) {
            $lower = mb_strtolower(trim($alias));
            if ($lower !== '') {
                $needles[$lower] = true;
            }
        }

        return array_keys($needles);
    }
}
