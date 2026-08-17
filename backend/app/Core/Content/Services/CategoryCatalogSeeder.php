<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content\Services;

/**
 * Seeds bundled content categories when registry is empty (It.84a).
 */
final class CategoryCatalogSeeder
{
    public function __construct(
        private CategoryRepository $repository,
    ) {
    }

    public function seedIfEmpty(): void
    {
        if ($this->repository->list() !== []) {
            $this->seedMissingBundled();

            return;
        }

        foreach ($this->bundledCategories() as $slug => $label) {
            $this->repository->save($slug, $label);
        }
    }

    public function seedMissingBundled(): void
    {
        foreach ($this->bundledCategories() as $slug => $label) {
            if ($this->repository->get($slug) !== null) {
                continue;
            }

            $this->repository->save($slug, $label);
        }
    }

    /**
     * @return array<string, string> slug => label
     */
    private function bundledCategories(): array
    {
        return [
            'news' => 'News',
            'security' => 'Security',
            'tutorials' => 'Tutorials',
            'product' => 'Product',
        ];
    }
}
