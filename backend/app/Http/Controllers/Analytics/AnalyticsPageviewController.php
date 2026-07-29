<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Analytics;

use InvalidArgumentException;
use PaginiumCMS\Core\Analytics\Services\AnalyticsManager;
use PaginiumCMS\Core\Analytics\Services\PageviewPathValidator;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Public SPA pageview beacon (records visits when static nginx serves the frontend).
 */
final class AnalyticsPageviewController
{
    public function __construct(
        private AnalyticsManager $analytics,
        private JsonResponder $json
    ) {
    }

    public function track(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $rawBody = (string) $request->getBody();
        /** @var array<string, mixed> $body */
        $body = json_decode($rawBody, true) ?: [];

        try {
            $uri = PageviewPathValidator::assertValid((string) ($body['uri'] ?? ''));
        } catch (InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        $duration = (int) ($body['duration_seconds'] ?? 0);
        if ($duration < 0 || $duration > 7200) {
            $duration = 0;
        }

        $referer = $request->getHeaderLine('Referer');
        if ($referer === '') {
            $referer = null;
        }

        $tracked = $this->analytics->trackPageViewFromRequest($request, $uri, $referer, $duration);

        return $this->json->success($response, [
            'tracked' => $tracked,
            'skipped' => !$tracked,
        ]);
    }
}
