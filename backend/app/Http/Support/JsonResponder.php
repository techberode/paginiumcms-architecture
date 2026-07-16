<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use Psr\Http\Message\ResponseInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Jednotný JSON responder pre HTTP controllery (Iterácia 19).
 */
final class JsonResponder
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public function success(
        ResponseInterface $response,
        mixed $data,
        int $status = 200,
        ?string $message = null,
        ?PaginationMeta $meta = null
    ): ResponseInterface {
        $payload = ['success' => true, 'data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($meta !== null) {
            $payload['meta'] = $meta->toArray();
        }

        return $this->write($response, $payload, $status);
    }

    /**
     * @param array<int|string, mixed> $items
     */
    public function paginated(
        ResponseInterface $response,
        array $items,
        PaginationMeta $meta,
        int $status = 200
    ): ResponseInterface {
        return $this->success($response, $items, $status, null, $meta);
    }

    /**
     * @param array<string, array<int, string>|string> $errors
     */
    public function error(
        ResponseInterface $response,
        string $message,
        int $status = 400,
        ?array $errors = null
    ): ResponseInterface {
        $payload = [
            'success' => false,
            'error' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return $this->write($response, $payload, $status);
    }

    /**
     * @param array<int|string, mixed> $payload
     */
    private function write(ResponseInterface $response, array $payload, int $status): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, self::JSON_FLAGS));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
