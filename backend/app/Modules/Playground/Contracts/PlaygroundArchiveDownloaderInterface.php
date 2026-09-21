<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Playground\Contracts;

/**
 * Fetches a playground pack archive after OutboundUrlGuard (It.95d).
 */
interface PlaygroundArchiveDownloaderInterface
{
    /**
     * @return string raw ZIP bytes
     */
    public function download(string $url, string $token): string;
}
