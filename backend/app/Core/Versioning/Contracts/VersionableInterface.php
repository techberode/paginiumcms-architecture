<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Versioning\Contracts;

use PaginiumCMS\Core\Versioning\Models\Version;

interface VersionableInterface
{
    public function createVersion(string $contentId, string $contentType, string $content, string $frontMatter, string $userId, string $message = ''): Version;
    public function getVersion(string $contentId, int $version): ?Version;
    public function getLastVersion(string $contentId): ?Version;
    public function getVersions(string $contentId): array;
    public function restoreVersion(string $contentId, int $version): bool;
    public function deleteVersions(string $contentId, int $keep = 10): int;
    public function getDiff(string $contentId, int $from, int $to): ?array;
}
