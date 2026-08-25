<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Content;

use PaginiumCMS\Core\Content\Models\CategoryRecord;
use PaginiumCMS\Core\Content\Services\CategoryCatalogSeeder;
use PaginiumCMS\Core\Content\Services\CategoryRepository;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkIdsParser;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Content category taxonomy API (It.84a).
 */
final class CategoriesController
{
    public function __construct(
        private CategoryRepository $categories,
        private CategoryCatalogSeeder $seeder,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->seeder->seedMissingBundled();

        return $this->json->success($response, [
            'categories' => $this->categories->list(),
        ]);
    }

    public function publicIndex(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->seeder->seedMissingBundled();

        return $this->json->success($response, [
            'categories' => $this->categories->list(),
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        $slug = CategoryRecord::normalizeSlug((string) ($data['slug'] ?? ''));
        $label = trim((string) ($data['label'] ?? ''));

        if ($slug === '') {
            return $this->json->validation($response, 'Invalid category slug.', ['slug' => ['Invalid slug']]);
        }

        try {
            $record = $this->categories->save($slug, $label);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        return $this->json->success($response, $record->toArray(), 201, 'Category saved');
    }

    /**
     * @param array<string, string> $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = CategoryRecord::normalizeSlug((string) ($args['slug'] ?? ''));
        if ($slug === '' || $this->categories->get($slug) === null) {
            return $this->json->error($response, 'Category not found', 404);
        }

        $data = RequestJsonBody::decode($request);
        $label = trim((string) ($data['label'] ?? ''));

        try {
            $record = $this->categories->save($slug, $label);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        return $this->json->success($response, $record->toArray(), 200, 'Category updated');
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $slug = CategoryRecord::normalizeSlug((string) ($args['slug'] ?? ''));
        if ($slug === '' || $this->categories->get($slug) === null) {
            return $this->json->error($response, 'Category not found', 404);
        }

        try {
            $this->categories->delete($slug);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        return $this->json->success($response, ['slug' => $slug, 'removed' => true]);
    }

    public function bulkDelete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $slugs = BulkIdsParser::fromRequest($request);
        if ($slugs === []) {
            return $this->json->error($response, 'No categories selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($slugs as $slug) {
            $normalized = CategoryRecord::normalizeSlug($slug);
            if ($normalized === '' || $this->categories->get($normalized) === null) {
                $batch->addFailure($slug, 'Category not found');
                continue;
            }

            try {
                $this->categories->delete($normalized);
                $batch->addSuccess($normalized);
            } catch (RuntimeException $exception) {
                $batch->addFailure($normalized, $exception->getMessage());
            }
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Categories deleted');
    }
}
