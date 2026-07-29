<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Webhooks;

use PaginiumCMS\Core\SystemUpdate\Services\SystemUpdateWebhookService;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GitHub release webhook — auto-deploy on published release (It.63 v3).
 */
final class GitHubReleaseWebhookController
{
    public function __construct(
        private SystemUpdateWebhookService $webhook,
        private JsonResponder $json
    ) {
    }

    public function release(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $rawBody = (string) $request->getBody();
        $result = $this->webhook->handleRelease(
            $rawBody,
            $request->getHeaderLine('X-GitHub-Event'),
            $request->getHeaderLine('X-Hub-Signature-256'),
            $request->getHeaderLine('X-GitHub-Delivery') ?: null
        );

        $status = (int) $result['http_status'];
        unset($result['http_status'], $result['ok']);

        if ($status >= 400) {
            return $this->json->error(
                $response,
                is_string($result['error'] ?? null) ? $result['error'] : 'Webhook rejected',
                $status
            );
        }

        return $this->json->success($response, $result, $status);
    }
}
