<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Debug;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Logging\Services\DebugEventLogger;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\LogSanitizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DebugController
{
    private const MAX_CONTEXT_BYTES = 8192;

    public function __construct(private JsonResponder $json)
    {
    }

    public function clientEvent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!DebugEventLogger::isEnabled()) {
            return $response->withStatus(204);
        }

        $payload = $this->parseJsonBody($request);
        $event = LogSanitizer::value((string) ($payload['event'] ?? 'frontend.event'), 128);
        $context = is_array($payload['context'] ?? null) ? $payload['context'] : [];

        $context['url'] = $context['url'] ?? (string) ($payload['url'] ?? '');
        $context['user_agent'] = LogSanitizer::value($request->getHeaderLine('User-Agent'), 512);
        $context = LogSanitizer::context($context);

        $encoded = json_encode($context, JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded) || strlen($encoded) > self::MAX_CONTEXT_BYTES) {
            $context = ['truncated' => true, 'user_agent' => $context['user_agent']];
        }

        DebugEventLogger::log('frontend', $event, $context);

        return $this->json->respond($response, ['success' => true]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function parseJsonBody(ServerRequestInterface $request): array
    {
        $data = RequestJsonBody::decode($request);

        return is_array($data) ? $data : [];
    }
}
