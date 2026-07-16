<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Storage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Servuje statické súbory z backend/storage/ (médiá, uploady).
 */
class StorageController
{
    public function __construct(
        private string $storageRoot
    ) {
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function serve(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $relativePath = ltrim((string) ($args['path'] ?? ''), '/');
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return $response->withStatus(404);
        }

        $storageRoot = realpath($this->storageRoot);
        if ($storageRoot === false) {
            return $response->withStatus(404);
        }

        $candidate = $storageRoot . '/' . $relativePath;
        $realPath = realpath($candidate);

        if ($realPath === false || !is_file($realPath) || !str_starts_with($realPath, $storageRoot . DIRECTORY_SEPARATOR)) {
            return $response->withStatus(404);
        }

        $mime = mime_content_type($realPath) ?: 'application/octet-stream';
        $stream = fopen($realPath, 'rb');
        if ($stream === false) {
            return $response->withStatus(500);
        }

        $response->getBody()->write((string) stream_get_contents($stream));
        fclose($stream);

        return $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('Cache-Control', 'public, max-age=86400');
    }
}
