<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Debug;

use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DebugController
{
    public function __construct(private JsonResponder $json)
    {
    }

    public function clientEvent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!DebugEventLogger::isEnabled()) {
            return $response->withStatus(204);
        }

        $payload = $this->parseJsonBody($request);
        $event = (string) ($payload['event'] ?? 'frontend.event');
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        $context['url'] = $context['url'] ?? (string) ($payload['url'] ?? '');
        $context['user_agent'] = $request->getHeaderLine('User-Agent');

        DebugEventLogger::log('frontend', $event, $context);

        return $this->json->respond($response, ['success' => true]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }
}
