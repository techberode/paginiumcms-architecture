<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Snippets\Services;

use PaginiumCMS\Core\Cache\ContentCacheService;

/**
 * Invalidates cached pages/articles that embed a snippet (It.81f).
 */
final class SnippetInvalidationService
{
    public function __construct(
        private SnippetReferenceScanner $scanner,
        private ContentCacheService $contentCache,
    ) {
    }

    public function invalidateForSnippet(string $snippetName): int
    {
        $references = $this->scanner->findReferences($snippetName);
        $seen = [];

        foreach ($references as $reference) {
            $key = $reference['type'] . ':' . $reference['slug'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ($reference['type'] === 'article') {
                $this->contentCache->invalidateArticle($reference['slug']);
            } else {
                $this->contentCache->invalidatePage($reference['slug']);
            }
        }

        if ($references === []) {
            $this->contentCache->invalidatePage();
            $this->contentCache->invalidateArticle();
        }

        return count($seen);
    }
}
