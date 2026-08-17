<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\Content\ContentSlug;
use PaginiumCMS\Core\Content\LocalizedContentWriter;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Contracts\ContentStorageInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Content;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Exception\FileNotFoundException;
use PaginiumCMS\Core\Git\Services\GitPublishDispatcher;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Http\Support\PaginationQuery;

/**
 * Repozitár pre prácu s obsahom.
 *
 * Spravuje CRUD operácie pre stránky a články. Podporuje Markdown aj JSON formát
 * a udržiava flat-file index pre rýchle listy (Iterácia 19).
 */
class ContentRepository implements ContentRepositoryInterface
{
    /** @var array<string, class-string<Content>> */
    private array $typeMapping = [
        'page' => Page::class,
        'article' => Article::class,
    ];

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private ContentIndexService $index,
        private MarkdownContentStorage $markdownStorage,
        private JsonContentStorage $jsonStorage,
        private SettingsRepositoryInterface $settings,
        private StorageInterface $storageLayer,
        private GitPublishDispatcher $gitPublishDispatcher,
        private LocalizedContentWriter $localizedWriter,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function findByPath(string $relativePath): ?Content
    {
        if (!$this->reader->exists($relativePath)) {
            return null;
        }

        try {
            $raw = $this->reader->read($relativePath);
            $storage = $this->storageForPath($relativePath);
            $parsed = $storage->parse($raw);
            $type = $this->determineType($relativePath);
            $className = $this->typeMapping[$type] ?? Page::class;
            /** @var Page|Article $object */
            $object = new $className();

            $object->setPath($relativePath);
            $object->setFrontMatter($parsed['frontMatter']);
            $object->setContent($parsed['content']);
            $object->setHtml($parsed['html']);

            $info = $this->reader->getInfo($relativePath);
            $object->setSize($info['size']);
            $object->setModifiedAt($info['mtime']);

            $slugBeforeRepair = trim($object->getSlug());
            $documentRepaired = $this->localizedWriter->hydrateFlatFieldsFromCanonical($object);
            // Repair in memory only — never persist on read (avoids revision bumps / 409 conflicts).
            if ($documentRepaired && $slugBeforeRepair === '' && $object->getSlug() !== '') {
                $this->persistRepairedIdentity($object);
            }

            return $object;
        } catch (FileNotFoundException) {
            return null;
        } catch (FlatFileException) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug, string $type = 'page'): ?Content
    {
        $this->index->ensureBuilt($this);
        $all = $this->index->query(
            $type,
            new PaginationQuery(1, PaginationQuery::MAX_PER_PAGE, '', '-updatedAt', [])
        );

        foreach ($all['entries'] as $entry) {
            if ($entry->slug === $slug) {
                return $this->findByPath($entry->path);
            }
        }

        // Index môže byť neaktuálny – fallback na disk.
        $total = $all['total'];
        if ($total > PaginationQuery::MAX_PER_PAGE) {
            $page = 1;
            while (($page - 1) * PaginationQuery::MAX_PER_PAGE < $total) {
                $batch = $this->index->query(
                    $type,
                    new PaginationQuery($page, PaginationQuery::MAX_PER_PAGE, '', '-updatedAt', [])
                );
                foreach ($batch['entries'] as $entry) {
                    if ($entry->slug === $slug) {
                        return $this->findByPath($entry->path);
                    }
                }
                $page++;
            }
        }

        $byBasename = $this->findByPathBasename($slug, $type);
        if ($byBasename !== null) {
            return $byBasename;
        }

        return $this->findBySlugScanningDisk($slug, $type);
    }

    /**
     * {@inheritDoc}
     * @param array<int|string, mixed> $filters
     * @return array<int, Page>
     */
    public function findAllPages(array $filters = []): array
    {
        $items = $this->findAll('pages', $filters);

        return array_values(array_filter($items, static fn (Content $c): bool => $c instanceof Page));
    }

    /**
     * {@inheritDoc}
     * @param array<int|string, mixed> $filters
     * @return array<int, Article>
     */
    public function findAllArticles(array $filters = []): array
    {
        $items = $this->findAll('blog', $filters);

        return array_values(array_filter($items, static fn (Content $c): bool => $c instanceof Article));
    }

    /**
     * {@inheritDoc}
     * @return array{items: array<int, Page>, total: int}
     */
    public function findPagesPaginated(PaginationQuery $query): array
    {
        $result = $this->findPaginated('page', $query);

        return [
            'items' => array_values(array_filter(
                $result['items'],
                static fn (Content $c): bool => $c instanceof Page
            )),
            'total' => $result['total'],
        ];
    }

    /**
     * {@inheritDoc}
     * @return array{items: array<int, Article>, total: int}
     */
    public function findArticlesPaginated(PaginationQuery $query): array
    {
        $result = $this->findPaginated('article', $query);

        return [
            'items' => array_values(array_filter(
                $result['items'],
                static fn (Content $c): bool => $c instanceof Article
            )),
            'total' => $result['total'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function listDistinctTags(string $type, array $filters = []): array
    {
        return $this->index->listDistinctTags($type, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function listDistinctCategories(string $type, array $filters = []): array
    {
        return $this->index->listDistinctCategories($type, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function countIndexed(string $type, array $filters = []): int
    {
        return $this->index->countMatching($type, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function save(Content $content): void
    {
        $resolvedSlug = ContentSlug::resolveSlug(
            $content->getSlug(),
            $content->getTitle(),
            $content->getPath()
        );
        if ($resolvedSlug === '') {
            throw new FlatFileException('Obsah musí mať platný slug');
        }
        if ($resolvedSlug !== $content->getSlug()) {
            $content->setSlug($resolvedSlug);
        }

        $storage = $this->activeStorage();
        $path = $content->getPath();

        if ($path === '') {
            $directory = $content instanceof Article ? 'blog' : 'pages';
            $path = $storage->buildPath($directory, $content->getSlug());
            $content->setPath($path);
        }

        $serialized = $storage->serialize($this->normalizeFrontMatter($content->getFrontMatter()), $content->getContent());
        if ($storage->format() === 'json') {
            $this->storageLayer->write($path, $serialized, true);
        } else {
            $this->writer->write($path, $serialized, true);
        }

        try {
            $this->gitPublishDispatcher->afterContentStored($path, $serialized);
        } catch (\Throwable) {
            // SSOT write succeeded; Git distribution failures are handled separately.
        }

        $info = $this->reader->getInfo($path);
        $content->setSize($info['size']);
        $content->setModifiedAt($info['mtime']);

        $type = $content instanceof Article ? 'article' : 'page';
        $this->index->upsertFromContent($content, $type);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Content $content, bool $permanent = false): void
    {
        $path = $content->getPath();

        if ($path === '') {
            throw new FlatFileException('Obsah nemá nastavenú cestu');
        }

        $type = $content instanceof Article ? 'article' : 'page';
        $slug = $content->getSlug();

        $this->writer->delete($path, !$permanent);
        $this->index->remove($type, $slug);
    }

    /**
     * {@inheritDoc}
     * @param array<int|string, mixed> $filters
     */
    public function count(string $type, array $filters = []): int
    {
        $directory = $type === 'article' ? 'blog' : 'pages';
        $files = $this->listContentFiles($directory);

        if ($filters === []) {
            return count($files);
        }

        $count = 0;
        foreach ($files as $file) {
            $fullPath = $this->normalizeDirectoryPath($directory, $file);

            try {
                $raw = $this->reader->read($fullPath);
                $frontMatter = $this->storageForPath($fullPath)->parse($raw)['frontMatter'];

                $matches = true;
                foreach ($filters as $key => $value) {
                    if (($frontMatter[$key] ?? null) !== $value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    $count++;
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return $count;
    }

    /**
     * Vylistuje obsahové súbory (*.md, *.json) v adresári.
     * Neexistujúci adresár (napr. ešte nevytvorený `content/blog`) znamená prázdny
     * zoznam – čítanie obsahu nesmie hádzať 500, keď typ obsahu zatiaľ nemá žiadne položky.
     *
     * @return array<int|string, mixed>
     */
    private function listContentFiles(string $directory): array
    {
        try {
            return array_merge(
                $this->reader->listFiles($directory, '*.md'),
                $this->reader->listFiles($directory, '*.json')
            );
        } catch (FileNotFoundException) {
            return [];
        }
    }

    /**
     * @return array{items: array<int, Content>, total: int}
     */
    private function findPaginated(string $type, PaginationQuery $query): array
    {
        $this->index->ensureBuilt($this);
        $result = $this->index->query($type, $query);

        $items = [];
        foreach ($result['entries'] as $entry) {
            $content = $this->findByPath($entry->path);
            if ($content !== null) {
                $items[] = $content;
            }
        }

        return ['items' => $items, 'total' => $result['total']];
    }

    /**
     * @param array<int|string, mixed> $filters
     * @return array<int, Content>
     */
    private function findAll(string $directory, array $filters = []): array
    {
        $files = $this->listContentFiles($directory);

        $results = [];

        foreach ($files as $file) {
            $fullPath = $this->normalizeDirectoryPath($directory, $file);

            try {
                $content = $this->findByPath($fullPath);

                if ($content === null) {
                    continue;
                }

                $matches = true;
                foreach ($filters as $key => $value) {
                    $frontMatter = $content->getFrontMatter();
                    if (($frontMatter[$key] ?? null) !== $value) {
                        $matches = false;
                        break;
                    }
                }

                if ($matches) {
                    $results[] = $content;
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return $results;
    }

    private function findByPathBasename(string $slug, string $type): ?Content
    {
        $directory = $type === 'article' ? 'blog' : 'pages';
        foreach ($this->listContentFiles($directory) as $file) {
            $fullPath = $this->normalizeDirectoryPath($directory, $file);
            $basename = ContentSlug::slugFromStoragePath($fullPath);
            if ($basename === $slug) {
                return $this->findByPath($fullPath);
            }
        }

        return null;
    }

    private function persistRepairedIdentity(Content $content): void
    {
        $storage = $this->activeStorage();
        $directory = $content instanceof Article ? 'blog' : 'pages';
        $targetPath = $storage->buildPath($directory, $content->getSlug());
        $currentPath = $content->getPath();

        if ($currentPath === '') {
            $content->setPath($targetPath);
            $currentPath = $targetPath;
        }

        if ($this->reader->exists($currentPath)) {
            $raw = $this->reader->read($currentPath);
            if ($currentPath !== $targetPath) {
                if ($storage->format() === 'json') {
                    $this->storageLayer->write($targetPath, $raw, true);
                } else {
                    $this->writer->write($targetPath, $raw, true);
                }
                $this->writer->delete($currentPath, true);
                $content->setPath($targetPath);
                $currentPath = $targetPath;
            }

            $patched = $this->patchSlugInStoredDocument($raw, $content->getSlug(), $storage->format());
            if ($patched !== null) {
                if ($storage->format() === 'json') {
                    $this->storageLayer->write($currentPath, $patched, true);
                } else {
                    $this->writer->write($currentPath, $patched, true);
                }
            }
        }

        $type = $content instanceof Article ? 'article' : 'page';
        if ($currentPath !== '') {
            $this->index->removeByPath($type, $currentPath);
        }
        $this->index->upsertFromContent($content, $type);
    }

    private function patchSlugInStoredDocument(string $raw, string $slug, string $format): ?string
    {
        if ($format === 'json') {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return null;
            }
            $decoded['slug'] = $slug;

            return \PaginiumCMS\Support\JsonHelper::encode(
                $decoded,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }

        if (!preg_match('/^---\s*\R(.*?)\R---\s*\R/s', $raw, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $frontMatterBlock = $matches[1][0];
        $rest = substr($raw, (int) $matches[0][1] + strlen($matches[0][0]));
        $lines = preg_split('/\R/', $frontMatterBlock) ?: [];
        $found = false;
        foreach ($lines as $index => $line) {
            if (str_starts_with($line, 'slug:')) {
                $lines[$index] = 'slug: ' . $slug;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $lines[] = 'slug: ' . $slug;
        }

        return '---' . "\n" . implode("\n", $lines) . "\n---\n" . $rest;
    }

    private function findBySlugScanningDisk(string $slug, string $type): ?Content
    {
        $directory = $type === 'article' ? 'blog' : 'pages';
        $files = $this->listContentFiles($directory);

        foreach ($files as $file) {
            $fullPath = $this->normalizeDirectoryPath($directory, $file);

            try {
                $raw = $this->reader->read($fullPath);
                $frontMatter = $this->storageForPath($fullPath)->parse($raw)['frontMatter'];

                if (($frontMatter['slug'] ?? '') === $slug) {
                    return $this->findByPath($fullPath);
                }
            } catch (FlatFileException) {
                continue;
            }
        }

        return null;
    }

    private function activeStorage(): ContentStorageInterface
    {
        $format = (string) $this->settings->get('content.storageFormat', 'md');

        return $format === 'json' ? $this->jsonStorage : $this->markdownStorage;
    }

    private function storageForPath(string $path): ContentStorageInterface
    {
        return str_ends_with(strtolower($path), '.json')
            ? $this->jsonStorage
            : $this->markdownStorage;
    }

    private function normalizeDirectoryPath(string $directory, string $file): string
    {
        if (!str_starts_with($file, $directory . '/')) {
            return $directory . '/' . $file;
        }

        return $file;
    }

    /**
     * @param string $path Relatívna cesta.
     * @return 'page'|'article'
     */
    private function determineType(string $path): string
    {
        if (str_starts_with($path, 'blog/')) {
            return 'article';
        }

        return 'page';
    }

    /**
     * @param array<int|string, mixed> $frontMatter
     * @return array<string, mixed>
     */
    private function normalizeFrontMatter(array $frontMatter): array
    {
        /** @var array<string, mixed> $normalized */
        $normalized = [];
        foreach ($frontMatter as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
