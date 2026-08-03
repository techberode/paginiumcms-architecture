<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use PaginiumCMS\Core\Cache\CacheDriverFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Stream;

/**
 * Applies ETag / Last-Modified conditional semantics to safe public GET responses (It.69).
 */
final class HttpConditionalResponse
{
    public static function apply(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $body,
        ?int $lastModifiedUnix = null,
        int $maxAgeSeconds = 60,
    ): ResponseInterface {
        $etag = self::weakEtag($body);
        $response = $response
            ->withHeader('ETag', $etag)
            ->withHeader('Cache-Control', 'public, max-age=' . max(0, $maxAgeSeconds));

        if ($lastModifiedUnix !== null && $lastModifiedUnix > 0) {
            $response = $response->withHeader(
                'Last-Modified',
                gmdate('D, d M Y H:i:s', $lastModifiedUnix) . ' GMT'
            );
        }

        if (self::matchesNoneMatch($request->getHeaderLine('If-None-Match'), $etag)) {
            return self::notModifiedResponse($response);
        }

        if ($lastModifiedUnix !== null
            && self::matchesModifiedSince($request->getHeaderLine('If-Modified-Since'), $lastModifiedUnix)
        ) {
            return self::notModifiedResponse($response);
        }

        return $response;
    }

    /**
     * Applies validators only when the response is eligible for shared public caching.
     *
     * @param array<string, mixed> $engineSettings
     */
    public static function applyWhenEligible(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $engineSettings,
        bool $eligible,
        ?int $lastModifiedUnix = null,
    ): ResponseInterface {
        if ($response->getStatusCode() !== 200) {
            return $response->withHeader('Cache-Control', 'no-store');
        }

        if (!$eligible) {
            return $response->withHeader('Cache-Control', 'private, no-store');
        }

        if (!CacheDriverFactory::httpValidatorsEnabled($engineSettings)) {
            return $response;
        }

        return self::apply(
            $request,
            $response,
            (string) $response->getBody(),
            $lastModifiedUnix,
            CacheDriverFactory::defaultTtlFromEngineSettings($engineSettings)
        );
    }

    private static function notModifiedResponse(ResponseInterface $response): ResponseInterface
    {
        $handle = fopen('php://memory', 'rb+');
        if ($handle === false) {
            return $response->withStatus(304);
        }

        return $response->withStatus(304)->withBody(new Stream($handle));
    }

    public static function weakEtag(string $body): string
    {
        return 'W/"' . hash('sha256', $body) . '"';
    }

    private static function matchesNoneMatch(string $header, string $etag): bool
    {
        $header = trim($header);
        if ($header === '' || $header === '*') {
            return false;
        }

        foreach (explode(',', $header) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === $etag || $candidate === trim($etag, 'W/')) {
                return true;
            }
        }

        return false;
    }

    private static function matchesModifiedSince(string $header, int $lastModifiedUnix): bool
    {
        $header = trim($header);
        if ($header === '') {
            return false;
        }

        $since = strtotime($header);
        if ($since === false) {
            return false;
        }

        return $lastModifiedUnix <= $since;
    }
}
