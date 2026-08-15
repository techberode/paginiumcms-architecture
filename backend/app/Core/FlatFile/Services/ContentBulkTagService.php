<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Support\Lang;

/**
 * Applies add/remove/replace tag mutations on flat-file content (It.81b).
 */
final class ContentBulkTagService
{
    /** @var list<string> */
    private const VALID_MODES = ['add', 'remove', 'replace'];

    private const MAX_TAG_LENGTH = 64;

    private const MAX_TAGS_PER_ITEM = 50;

    /**
     * @throws ValidationException
     */
    public function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        if ($mode === '') {
            return 'add';
        }

        if (!in_array($mode, self::VALID_MODES, true)) {
            throw new ValidationException([
                'mode' => [Lang::get('invalid_bulk_tag_mode', [], 'content')],
            ]);
        }

        return $mode;
    }

    /**
     * @param list<string> $tags
     *
     * @throws ValidationException
     */
    public function apply(Content $content, string $mode, array $tags): void
    {
        $mode = strtolower(trim($mode));
        if ($mode === '') {
            $mode = 'add';
        }

        if (!in_array($mode, self::VALID_MODES, true)) {
            throw new ValidationException([
                'mode' => [Lang::get('invalid_bulk_tag_mode', [], 'content')],
            ]);
        }

        $normalizedTags = $this->normalizeTags($tags);
        if ($mode !== 'remove' && $normalizedTags === []) {
            throw new ValidationException([
                'tags' => [Lang::get('tags_required', [], 'content')],
            ]);
        }

        $current = $this->normalizeTags($content->getTags());
        $next = match ($mode) {
            'add' => $this->mergeTags($current, $normalizedTags),
            'remove' => $this->removeTags($current, $normalizedTags),
            'replace' => $normalizedTags,
        };

        $content->setTags($next);
    }

    /**
     * @param mixed $value
     * @return list<string>
     *
     * @throws ValidationException
     */
    public function normalizeTags(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $tag) {
            if (!is_string($tag) && !is_int($tag) && !is_float($tag)) {
                continue;
            }

            $candidate = trim((string) $tag);
            if ($candidate === '') {
                continue;
            }

            if (strlen($candidate) > self::MAX_TAG_LENGTH) {
                throw new ValidationException([
                    'tags' => [Lang::get('invalid_tag_length', [], 'content')],
                ]);
            }

            if (!in_array($candidate, $normalized, true)) {
                $normalized[] = $candidate;
            }
        }

        if (count($normalized) > self::MAX_TAGS_PER_ITEM) {
            throw new ValidationException([
                'tags' => [Lang::get('too_many_tags', [], 'content')],
            ]);
        }

        return $normalized;
    }

    /**
     * @param list<string> $current
     * @param list<string> $incoming
     * @return list<string>
     */
    private function mergeTags(array $current, array $incoming): array
    {
        foreach ($incoming as $tag) {
            if (!in_array($tag, $current, true)) {
                $current[] = $tag;
            }
        }

        return $current;
    }

    /**
     * @param list<string> $current
     * @param list<string> $remove
     * @return list<string>
     */
    private function removeTags(array $current, array $remove): array
    {
        if ($remove === []) {
            return $current;
        }

        return array_values(array_filter(
            $current,
            static fn (string $tag): bool => !in_array($tag, $remove, true)
        ));
    }
}
