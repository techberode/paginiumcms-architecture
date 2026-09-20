<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\StaticSite;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Rebuilds the derived static tree from published SSOT (It.48 / 58g).
 */
final class StaticSiteGenerator
{
    public function __construct(
        private ContentRepositoryInterface $content,
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private StaticSiteSettings $settings,
        private StaticSiteCompiler $compiler,
    ) {
    }

    /**
     * @return array{
     *     success: bool,
     *     message: string,
     *     renderMode: string,
     *     generatedAt: int,
     *     written: int,
     *     removed: int,
     *     items: list<array{type: string, slug: string, path: string, fingerprint: string}>
     * }
     */
    public function rebuildAll(): array
    {
        $written = [];
        foreach ($this->content->findAllPages(['status' => 'published']) as $page) {
            $item = $this->compiler->writePublished($page, 'page');
            if ($item !== null) {
                $written[] = $item;
            }
        }
        foreach ($this->content->findAllArticles(['status' => 'published']) as $article) {
            $item = $this->compiler->writePublished($article, 'article');
            if ($item !== null) {
                $written[] = $item;
            }
        }

        $keep = [];
        foreach ($written as $item) {
            $keep[$item['type'] . ':' . $item['slug']] = true;
        }

        $removed = $this->removeOrphans($keep);
        $this->writeManifest($written);

        return [
            'success' => true,
            'message' => 'Static tree rebuilt',
            'renderMode' => $this->settings->renderMode(),
            'generatedAt' => time(),
            'written' => count($written),
            'removed' => $removed,
            'items' => $written,
        ];
    }

    /**
     * @return array{success: bool, message: string, action: string, type: string, slug: string, path?: string}
     */
    public function rebuildOne(string $type, string $slug): array
    {
        $type = StaticSitePath::assertType($type);
        $slug = StaticSitePath::assertSlug($slug);
        $found = $this->content->findBySlug($slug, $type);
        if ($found === null || $found->getStatus() !== 'published') {
            $this->compiler->removeOne($type, $slug);

            return [
                'success' => true,
                'message' => 'Static artifact removed (not published)',
                'action' => 'removed',
                'type' => $type,
                'slug' => $slug,
            ];
        }

        $item = $this->compiler->writePublished($found, $type);
        if ($item === null) {
            return [
                'success' => false,
                'message' => 'Could not compile static HTML',
                'action' => 'failed',
                'type' => $type,
                'slug' => $slug,
            ];
        }

        return [
            'success' => true,
            'message' => 'Static page compiled',
            'action' => 'written',
            'type' => $type,
            'slug' => $slug,
            'path' => $item['path'],
        ];
    }

    public function removeOne(string $type, string $slug): void
    {
        $this->compiler->removeOne($type, $slug);
    }

    /**
     * @return array{
     *     renderMode: string,
     *     autoRebuild: bool,
     *     generatedAt: int|null,
     *     pageCount: int,
     *     articleCount: int,
     *     writable: bool,
     *     tree: string,
     *     publicServe: bool,
     *     publicPrefix: string
     * }
     */
    public function status(): array
    {
        $manifest = $this->readManifest();
        $pageCount = 0;
        $articleCount = 0;
        foreach (($manifest['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (($item['type'] ?? '') === 'article') {
                $articleCount++;
            } else {
                $pageCount++;
            }
        }

        return [
            'renderMode' => $this->settings->renderMode(),
            'autoRebuild' => $this->settings->autoRebuildOnWrite(),
            'generatedAt' => isset($manifest['generatedAt']) ? (int) $manifest['generatedAt'] : null,
            'pageCount' => $pageCount,
            'articleCount' => $articleCount,
            'writable' => is_writable($this->writer->getBasePath()),
            'tree' => StaticSitePath::ROOT,
            'publicServe' => $this->settings->servesPublicHtml(),
            'publicPrefix' => StaticSitePath::PUBLIC_PREFIX,
        ];
    }

    /**
     * @param array<string, true> $keep
     */
    private function removeOrphans(array $keep): int
    {
        $removed = 0;
        foreach (['page', 'article'] as $type) {
            $dir = StaticSitePath::ROOT . '/' . StaticSitePath::directoryForType($type);
            try {
                $entries = $this->reader->listFiles($dir);
            } catch (\Throwable) {
                continue;
            }
            foreach ($entries as $name) {
                $slug = strtolower(trim((string) $name));
                if ($slug === '' || isset($keep[$type . ':' . $slug])) {
                    continue;
                }
                try {
                    $this->compiler->removeOne($type, $slug);
                    $removed++;
                } catch (\InvalidArgumentException) {
                    continue;
                }
            }
        }

        return $removed;
    }

    /**
     * @param list<array{type: string, slug: string, path: string, fingerprint: string}> $items
     */
    private function writeManifest(array $items): void
    {
        $payload = [
            'generatedAt' => time(),
            'renderMode' => $this->settings->renderMode(),
            'pageCount' => count(array_filter($items, static fn (array $row): bool => $row['type'] === 'page')),
            'articleCount' => count(array_filter($items, static fn (array $row): bool => $row['type'] === 'article')),
            'items' => $items,
        ];
        $this->writer->write(StaticSitePath::MANIFEST, JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), false);
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(): array
    {
        if (!$this->reader->exists(StaticSitePath::MANIFEST)) {
            return [];
        }
        try {
            $decoded = json_decode($this->reader->read(StaticSitePath::MANIFEST), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
