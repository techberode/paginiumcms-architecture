<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Import;

use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Support\JsonHelper;

/**
 * Parses Ghost Admin JSON export (posts + pages).
 *
 * @phpstan-import-type NormalizedImportRow from ImportRowTypes
 */
final class GhostJsonImporter
{
    /**
     * @return list<NormalizedImportRow>
     */
    public function parseFile(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new FlatFileException('Ghost export file is not readable: ' . $path);
        }

        return $this->parseJson((string) file_get_contents($path));
    }

    /**
     * @return list<NormalizedImportRow>
     */
    public function parseJson(string $json): array
    {
        try {
            $decoded = JsonHelper::decode($json);
        } catch (\Throwable $e) {
            throw new FlatFileException('Invalid Ghost JSON export: ' . $e->getMessage());
        }

        $posts = $this->extractPosts($decoded);
        $rows = [];

        foreach ($posts as $post) {
            $slug = trim((string) ($post['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $ghostType = strtolower(trim((string) ($post['type'] ?? 'post')));
            $type = $ghostType === 'page' ? 'page' : 'article';

            $html = trim((string) ($post['html'] ?? $post['plaintext'] ?? ''));
            $title = trim((string) ($post['title'] ?? $slug));
            $status = $this->mapStatus((string) ($post['status'] ?? ''));
            $date = trim((string) ($post['published_at'] ?? $post['created_at'] ?? ''));
            if ($date === '') {
                $date = gmdate('Y-m-d H:i:s');
            }

            /** @var list<string> $tags */
            $tags = [];
            if (isset($post['tags']) && is_array($post['tags'])) {
                foreach ($post['tags'] as $tag) {
                    if (is_array($tag)) {
                        $name = trim((string) ($tag['name'] ?? ''));
                        if ($name !== '') {
                            $tags[] = $name;
                        }
                    } elseif (is_string($tag) && trim($tag) !== '') {
                        $tags[] = trim($tag);
                    }
                }
            }

            $excerpt = trim(strip_tags((string) ($post['custom_excerpt'] ?? $post['excerpt'] ?? '')));

            $rows[] = ContentImportRowFactory::create(
                $type,
                $slug,
                $title,
                $html,
                $status,
                $date,
                $excerpt,
                $tags,
                'ghost',
            );
        }

        return $rows;
    }

    /**
     * @param array<int|string, mixed> $decoded
     * @return list<array<string, mixed>>
     */
    private function extractPosts(array $decoded): array
    {
        if (isset($decoded['posts']) && is_array($decoded['posts'])) {
            return $this->normalizePostList($decoded['posts']);
        }

        if (isset($decoded['db']) && is_array($decoded['db'])) {
            foreach ($decoded['db'] as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $data = $entry['data'] ?? null;
                if (is_array($data) && isset($data['posts']) && is_array($data['posts'])) {
                    return $this->normalizePostList($data['posts']);
                }
            }
        }

        throw new FlatFileException('Ghost export JSON does not contain posts');
    }

    /**
     * @param array<int|string, mixed> $posts
     * @return list<array<string, mixed>>
     */
    private function normalizePostList(array $posts): array
    {
        $normalized = [];
        foreach ($posts as $post) {
            if (is_array($post)) {
                /** @var array<string, mixed> $post */
                $normalized[] = $post;
            }
        }

        return $normalized;
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'published', 'sent' => 'published',
            default => 'draft',
        };
    }
}
