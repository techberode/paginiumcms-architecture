<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Security\Firewall;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads a bounded snapshot of the request body for WAF scanning (S-WAFBODY).
 *
 * Skips multipart uploads; rewinds seekable streams so downstream handlers keep working.
 */
final class FirewallRequestBodyReader
{
    private const MAX_BYTES = 65536;

    public function read(ServerRequestInterface $request): ?string
    {
        $method = strtoupper($request->getMethod());
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return null;
        }

        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        if (str_contains($contentType, 'multipart/form-data')) {
            return null;
        }

        $stream = $request->getBody();
        if (!$stream->isReadable()) {
            return null;
        }

        $contents = (string) $stream;
        if ($contents === '') {
            return null;
        }

        if (strlen($contents) > self::MAX_BYTES) {
            $contents = substr($contents, 0, self::MAX_BYTES);
        }

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return $contents;
    }
}
