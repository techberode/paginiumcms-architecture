<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Debug;

use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

final class DebugController
{
    public function clientEvent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!DebugEventLogger::isEnabled()) {
            return $this->json($response, ['success' => false, 'error' => 'Debug logging disabled'], 404);
        }

        $payload = $this->parseJsonBody($request);
        $event = (string) ($payload['event'] ?? 'frontend.event');
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        $context['url'] = $context['url'] ?? (string) ($payload['url'] ?? '');
        $context['user_agent'] = $request->getHeaderLine('User-Agent');

        DebugEventLogger::log('frontend', $event, $context);

        return $this->json($response, ['success' => true]);
    }

    /**
     * @return array<int|string, mixed>
 */private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<int|string, mixed> $payload
 */private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
