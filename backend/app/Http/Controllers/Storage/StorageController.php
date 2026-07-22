<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Storage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Servuje VÝHRADNE verejné statické médiá z backend/storage/.
 *
 * Bezpečnosť (audit 2026-07-22): táto route je verejná (bez AuthMiddleware),
 * preto NESMIE sprístupniť nič mimo verejného media podstromu. Flat-file dáta
 * (`data/users/*.json` s hashmi hesiel a 2FA tajomstvami, `data/settings.json`
 * so SMTP heslom, `logs/`, `backups/`) ležia tiež pod `storage/`, takže samotná
 * kontrola „vnútri storageRoot" nestačí. Servírujeme len allow-listnuté prefixy.
 */
class StorageController
{
    /**
     * Verejné podstromy relatívne k storageRoot, ktoré je bezpečné servírovať.
     * `app/content/media` = používateľské médiá, avatary, login pozadie.
     *
     * @var list<string>
     */
    private const PUBLIC_PREFIXES = [
        'app/content/media',
        'app/demo/media',
    ];

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
        if ($relativePath === '' || str_contains($relativePath, '..') || str_contains($relativePath, "\0")) {
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

        // Allow-list: súbor musí ležať vnútri verejného media podstromu.
        // Čokoľvek iné (data/, logs/, backups/, dev/, cache/) → 404.
        if (!$this->isWithinPublicPrefix($storageRoot, $realPath)) {
            return $response->withStatus(404);
        }

        $mime = mime_content_type($realPath) ?: 'application/octet-stream';
        $stream = fopen($realPath, 'rb');
        if ($stream === false) {
            return $response->withStatus(500);
        }

        $response->getBody()->write((string) stream_get_contents($stream));
        fclose($stream);

        // Aktívny obsah (SVG/HTML/XML) sa nikdy neservíruje inline v same-origin
        // kontexte, inak by vložený <script> spustil stored XSS.
        $isActiveMime = $mime === 'image/svg+xml'
            || str_contains($mime, 'html')
            || str_contains($mime, 'xml');

        $response = $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'public, max-age=86400');

        if ($isActiveMime) {
            $response = $response
                ->withHeader('Content-Disposition', 'attachment; filename="' . addslashes(basename($realPath)) . '"')
                ->withHeader('Content-Security-Policy', "sandbox; default-src 'none'");
        }

        return $response;
    }

    /**
     * Overí, že kanonická cesta leží vnútri niektorého verejného prefixu.
     */
    private function isWithinPublicPrefix(string $storageRoot, string $realPath): bool
    {
        foreach (self::PUBLIC_PREFIXES as $prefix) {
            $base = $storageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $prefix);
            // realpath prefixu (ak existuje) rieši symlinky; fallback na string prefix.
            $canonicalBase = realpath($base);
            $needle = ($canonicalBase !== false ? $canonicalBase : $base) . DIRECTORY_SEPARATOR;

            if (str_starts_with($realPath, $needle)) {
                return true;
            }
        }

        return false;
    }
}
