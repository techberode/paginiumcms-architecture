<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\StaticSite\StaticSiteGenerator;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Admin compile API for the derived static tree (Iteration 48). Separate from Git publish.
 */
final class StaticSiteController
{
    public function __construct(
        private StaticSiteGenerator $generator,
        private JsonResponder $json,
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->generator->status());
    }

    public function rebuild(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $request->getParsedBody();
        $payload = is_array($body) ? $body : [];
        $scope = strtolower(trim((string) ($payload['scope'] ?? 'all')));

        try {
            if ($scope === 'page') {
                $result = $this->generator->rebuildOne(
                    (string) ($payload['type'] ?? 'page'),
                    (string) ($payload['slug'] ?? '')
                );
            } else {
                $result = $this->generator->rebuildAll();
            }
        } catch (RuntimeException | \InvalidArgumentException $e) {
            return $this->json->error($response, $e->getMessage(), 422);
        }

        return $this->json->success(
            $response,
            $result,
            $result['success'] ? 200 : 422,
            $result['success'] ? 'Static tree updated' : 'Static rebuild failed'
        );
    }
}
