<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\FlatFile\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Core\Import\WordPressWxrImporter;
use PaginiumCMS\Support\JsonHelper;

/**
 * Imports pages/articles from JSON export bundles or WordPress WXR (It.80f / 80g).
 */
final class ContentImportService
{
    public function __construct(
        private ContentRepositoryInterface $repository,
        private WordPressWxrImporter $wordpress,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function importFromJsonPayload(array $payload, bool $dryRun): ContentImportResult
    {
        $result = new ContentImportResult();
        $items = $payload['items'] ?? null;

        if (!is_array($items)) {
            $result->addError('Invalid export payload: missing items array');

            return $result;
        }

        foreach ($items as $index => $row) {
            if (!is_array($row)) {
                $result->addError('Invalid item at index ' . $index);

                continue;
            }

            try {
                $this->importRow($row, $dryRun, $result);
            } catch (FlatFileException $e) {
                $result->addError('Item #' . $index . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    public function importFromJsonFile(string $path, bool $dryRun): ContentImportResult
    {
        if (!is_file($path) || !is_readable($path)) {
            $result = new ContentImportResult();
            $result->addError('JSON import file is not readable: ' . $path);

            return $result;
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = JsonHelper::decode((string) file_get_contents($path));
        } catch (\Throwable $e) {
            $result = new ContentImportResult();
            $result->addError('Failed to parse JSON: ' . $e->getMessage());

            return $result;
        }

        return $this->importFromJsonPayload($payload, $dryRun);
    }

    public function importFromWordPressFile(string $path, bool $dryRun): ContentImportResult
    {
        $result = new ContentImportResult();

        try {
            $rows = $this->wordpress->parseFile($path);
        } catch (FlatFileException $e) {
            $result->addError($e->getMessage());

            return $result;
        }

        foreach ($rows as $row) {
            try {
                $this->importRow([
                    'type' => $row['type'],
                    'slug' => $row['slug'],
                    'frontMatter' => [
                        'title' => $row['title'],
                        'slug' => $row['slug'],
                        'status' => $row['status'],
                        'date' => $row['date'],
                        'description' => $row['description'],
                        'tags' => $row['tags'],
                        'importSource' => 'wordpress',
                    ],
                    'content' => $row['content'],
                ], $dryRun, $result);
            } catch (FlatFileException $e) {
                $result->addError($row['type'] . '/' . $row['slug'] . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importRow(array $row, bool $dryRun, ContentImportResult $result): void
    {
        $type = (string) ($row['type'] ?? '');
        if (!in_array($type, ['page', 'article'], true)) {
            throw new FlatFileException('Unsupported content type: ' . $type);
        }

        $slug = $this->sanitizeSlug((string) ($row['slug'] ?? ''));
        if ($slug === '') {
            throw new FlatFileException('Missing slug');
        }

        /** @var array<string, mixed> $frontMatter */
        $frontMatter = is_array($row['frontMatter'] ?? null) ? $row['frontMatter'] : [];
        $frontMatter['slug'] = $slug;
        $frontMatter['title'] = trim((string) ($frontMatter['title'] ?? $slug));
        $frontMatter['status'] = (string) ($frontMatter['status'] ?? 'draft');

        $content = (string) ($row['content'] ?? '');
        $resolvedSlug = $this->resolveSlugCollision($slug, $type);
        if ($resolvedSlug !== $slug) {
            $frontMatter['slug'] = $resolvedSlug;
            $frontMatter['importOriginalSlug'] = $slug;
            $result->messages[] = sprintf('%s/%s: slug collision → %s', $type, $slug, $resolvedSlug);
        }

        if ($dryRun) {
            $result->addCreated(sprintf('[dry-run] would import %s/%s (%s)', $type, $resolvedSlug, $frontMatter['title']));

            return;
        }

        $model = $type === 'article' ? new Article() : new Page();
        $model->setFrontMatter($frontMatter);
        $model->setContent($content);
        $this->repository->save($model);

        $result->addCreated(sprintf('Imported %s/%s (%s)', $type, $resolvedSlug, $frontMatter['title']));
    }

    private function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    private function resolveSlugCollision(string $slug, string $type): string
    {
        if ($this->repository->findBySlug($slug, $type) === null) {
            return $slug;
        }

        $candidate = 'import-' . $slug;
        if ($this->repository->findBySlug($candidate, $type) === null) {
            return $candidate;
        }

        for ($i = 2; $i <= 99; ++$i) {
            $next = $candidate . '-' . $i;
            if ($this->repository->findBySlug($next, $type) === null) {
                return $next;
            }
        }

        throw new FlatFileException('Could not resolve slug collision for ' . $slug);
    }
}
