<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Git\Contracts;

use PaginiumCMS\Core\Git\Models\GitPublishResult;

/**
 * Distribution-layer Git publisher (Iteration 70).
 *
 * SSOT write happens before any publisher call. Failures here must not imply SSOT loss.
 */
interface GitPublisherInterface
{
    /**
     * @return array<string, mixed>
     */
    public function status(): array;

    /**
     * @param list<string> $relativePaths paths allow-listed under the repository (e.g. pages/foo.json)
     */
    public function stage(array $relativePaths): void;

    public function commit(string $message): GitPublishResult;

    public function push(): GitPublishResult;
}
