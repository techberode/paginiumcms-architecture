<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Services;

/**
 * Thin hook from content SSOT writes to Git distribution (Iteration 70).
 */
final class GitPublishDispatcher
{
    public function __construct(
        private GitPublishService $gitPublish,
    ) {
    }

    public function afterContentStored(string $contentPath, string $serializedBody): void
    {
        try {
            $fingerprint = hash('sha256', $serializedBody);
            $this->gitPublish->afterContentStored($contentPath, $fingerprint);
        } catch (\Throwable) {
            // Git failure must not roll back SSOT write.
        }
    }
}
