<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Reads JSON request bodies after Slim BodyParsingMiddleware (non-seekable php://input).
 */
final class RequestJsonBody
{
    /**
     * @return array<string, mixed>|null null when body is empty or not a JSON object
     */
    public static function decode(ServerRequestInterface $request): ?array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed)) {
            return $parsed;
        }

        if (is_object($parsed)) {
            /** @var array<string, mixed> */
            return (array) $parsed;
        }

        $raw = (string) $request->getBody();
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
