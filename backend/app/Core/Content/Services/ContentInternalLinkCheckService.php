<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Content;

/**
 * Validates internal links in Markdown/HTML body against flat-file content (no outbound HTTP).
 */
final class ContentInternalLinkCheckService
{
    public function __construct(
        private ContentRepositoryInterface $content,
    ) {
    }

    /**
     * @return array{ok: bool, issues: list<array{url: string, line: int, reason: string}>}
     */
    public function check(string $body, string $ownSlug, string $ownType, string $contentFormat = 'markdown'): array
    {
        $issues = [];
        $format = strtolower(trim($contentFormat));
        if ($format === '') {
            $format = 'markdown';
        }

        foreach ($this->collectUrls($body, $format) as $entry) {
            $this->inspectUrl($entry['url'], $entry['line'], $ownSlug, $ownType, $issues);
        }

        return [
            'ok' => $issues === [],
            'issues' => $issues,
        ];
    }

    /**
     * @return list<array{url: string, line: int}>
     */
    private function collectUrls(string $body, string $format): array
    {
        if ($format === 'tiptap_json') {
            return $this->collectUrlsFromTiptapJson($body);
        }

        if ($format === 'html') {
            return $this->collectUrlsFromHtml($body);
        }

        return $this->collectUrlsFromMarkdown($body);
    }

    /**
     * @return list<array{url: string, line: int}>
     */
    private function collectUrlsFromMarkdown(string $body): array
    {
        $found = [];
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];

        foreach ($lines as $index => $line) {
            $lineNo = $index + 1;
            if (preg_match_all('/\[[^\]]*\]\(([^)]+)\)/', $line, $mdMatches, PREG_SET_ORDER)) {
                foreach ($mdMatches as $match) {
                    $found[] = ['url' => (string) $match[1], 'line' => $lineNo];
                }
            }
            if (preg_match_all('/\bhref\s*=\s*["\']([^"\']+)["\']/i', $line, $hrefMatches, PREG_SET_ORDER)) {
                foreach ($hrefMatches as $match) {
                    $found[] = ['url' => (string) $match[1], 'line' => $lineNo];
                }
            }
        }

        return $found;
    }

    /**
     * @return list<array{url: string, line: int}>
     */
    private function collectUrlsFromHtml(string $body): array
    {
        $found = [];
        if (preg_match_all('/\bhref\s*=\s*["\']([^"\']+)["\']/i', $body, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $href = (string) $match[0];
                $offset = (int) $match[1];
                $line = substr_count(substr($body, 0, $offset), "\n") + 1;
                $found[] = ['url' => $href, 'line' => $line];
            }
        }

        return $found;
    }

    /**
     * @return list<array{url: string, line: int}>
     */
    private function collectUrlsFromTiptapJson(string $body): array
    {
        $trimmed = trim($body);
        if ($trimmed === '') {
            return [];
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->collectUrlsFromMarkdown($body);
        }

        if (!is_array($decoded)) {
            return [];
        }

        $found = [];
        $this->walkTiptapNode($decoded, $found);

        return $found;
    }

    /**
     * @param array<string, mixed> $node
     * @param list<array{url: string, line: int}> $found
     */
    private function walkTiptapNode(array $node, array &$found): void
    {
        $marks = $node['marks'] ?? null;
        if (is_array($marks)) {
            foreach ($marks as $mark) {
                if (!is_array($mark) || ($mark['type'] ?? '') !== 'link') {
                    continue;
                }
                $attrs = $mark['attrs'] ?? null;
                if (!is_array($attrs)) {
                    continue;
                }
                $href = trim((string) ($attrs['href'] ?? ''));
                if ($href !== '') {
                    $found[] = ['url' => $href, 'line' => 1];
                }
            }
        }

        $content = $node['content'] ?? null;
        if (!is_array($content)) {
            return;
        }

        foreach ($content as $child) {
            if (is_array($child)) {
                $this->walkTiptapNode($child, $found);
            }
        }
    }

    /**
     * @param list<array{url: string, line: int, reason: string}> $issues
     */
    private function inspectUrl(string $url, int $line, string $ownSlug, string $ownType, array &$issues): void
    {
        $trimmed = trim($url);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || str_starts_with($trimmed, 'mailto:')
            || str_starts_with($trimmed, 'tel:') || str_starts_with($trimmed, 'javascript:')) {
            return;
        }

        $path = $this->normalizeToPath($trimmed);
        if ($path === null) {
            return;
        }

        $resolved = $this->resolveInternalTarget($path, $ownType);
        if ($resolved === null) {
            return;
        }

        [$type, $slug] = $resolved;
        if ($type === $ownType && $slug === $ownSlug) {
            return;
        }

        $target = $this->content->findBySlug($slug, $type);
        if ($target === null) {
            $issues[] = [
                'url' => $trimmed,
                'line' => $line,
                'reason' => 'missing_content',
            ];

            return;
        }

        if (!$this->isPubliclyReachable($target)) {
            $issues[] = [
                'url' => $trimmed,
                'line' => $line,
                'reason' => 'not_published',
            ];
        }
    }

    private function normalizeToPath(string $url): ?string
    {
        if (preg_match('#^https?://#i', $url)) {
            $parts = parse_url($url);
            if (!is_array($parts)) {
                return null;
            }

            $path = (string) ($parts['path'] ?? '');
            if ($path === '') {
                return null;
            }

            return $this->collapsePathSegments($path);
        }

        $path = str_replace('\\', '/', trim($url));
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, './')) {
            $path = substr($path, 2);
        } elseif (preg_match('#^(?:\.\./)+#', $path) === 1) {
            $path = (string) preg_replace('#^(?:\.\./)+#', '', $path);
        }

        $path = trim($path, '/');
        if ($path === '') {
            return null;
        }

        if (!str_contains($path, '/')) {
            return '/' . $path;
        }

        return $this->collapsePathSegments('/' . $path);
    }

    private function collapsePathSegments(string $path): string
    {
        $parts = explode('/', trim($path, '/'));
        $stack = [];
        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($stack);

                continue;
            }
            $stack[] = $part;
        }

        if ($stack === []) {
            return '/';
        }

        return '/' . implode('/', $stack);
    }

    private function isPubliclyReachable(Content $content): bool
    {
        return $content->getStatus() === 'published';
    }

    /**
     * @return array{0: string, 1: string}|null [type, slug]
     */
    private function resolveInternalTarget(string $path, string $ownType): ?array
    {
        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];
        $path = rtrim($path, '/');
        if ($path === '' || $path === '/') {
            return null;
        }

        $path = strtolower($path);

        if (preg_match('#^/(?:[a-z]{2}/)?blog/([a-z0-9][a-z0-9_-]*)$#', $path, $m) === 1) {
            return ['article', $m[1]];
        }

        if (preg_match('#^/(?:[a-z]{2}/)?articles/([a-z0-9][a-z0-9_-]*)$#', $path, $m) === 1) {
            return ['article', $m[1]];
        }

        if (preg_match('#^/(?:[a-z]{2}/)?pages/([a-z0-9][a-z0-9_-]*)$#', $path, $m) === 1) {
            return ['page', $m[1]];
        }

        if (preg_match('#^/(?:[a-z]{2}/)?([a-z0-9][a-z0-9_-]*)$#', $path, $m) === 1) {
            $segment = $m[1];
            if (in_array($segment, ['blog', 'pages', 'articles', 'admin', 'api', 'preview'], true)) {
                return null;
            }

            if ($ownType === 'article') {
                return ['article', $segment];
            }

            return ['page', $segment];
        }

        return null;
    }
}
