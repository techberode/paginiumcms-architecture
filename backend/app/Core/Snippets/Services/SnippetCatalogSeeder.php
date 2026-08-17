<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Snippets\Services;

use PaginiumCMS\Support\JsonHelper;

/**
 * Seeds bundled reusable snippets when registry is empty (It.81f).
 */
final class SnippetCatalogSeeder
{
    public function __construct(
        private SnippetRepository $repository,
        private SnippetRegistry $registry,
    ) {
    }

    public function seedIfEmpty(): void
    {
        if ($this->registry->all() !== []) {
            $this->seedMissingBundled();

            return;
        }

        foreach ($this->bundledSnippets() as $name => $payload) {
            $this->repository->save($name, $payload);
        }
    }

    public function seedMissingBundled(): void
    {
        foreach ($this->bundledSnippets() as $name => $payload) {
            if ($this->registry->get($name) !== null) {
                continue;
            }

            $this->repository->save($name, $payload);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function bundledSnippets(): array
    {
        return [
            'author-bio' => [
                'name' => 'author-bio',
                'title' => 'Author bio',
                'format' => 'markdown',
                'version' => 1,
                'enabled' => true,
                'body' => "**Jane Doe** is a technical writer at PaginiumCMS. She covers editorial workflow, flat-file CMS design, and developer experience.\n",
            ],
            'cta-banner' => [
                'name' => 'cta-banner',
                'title' => 'CTA banner',
                'format' => 'html',
                'version' => 1,
                'enabled' => true,
                'body' => '<div class="pg-card"><div class="pg-card-body"><p><strong>Ready to try PaginiumCMS?</strong></p><p><a href="/contact">Contact us</a> for a guided demo.</p></div></div>',
            ],
        ];
    }
}
