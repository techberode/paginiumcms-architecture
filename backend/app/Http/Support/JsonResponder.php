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
     * Validačná chyba (422) – rovnaký tvar ako ApiErrorHandler pre ValidationException.
     *
     * @param array<string, array<int, string>|string> $errors
     */
    public function validation(
        ResponseInterface $response,
        string $message,
        array $errors,
        int $status = 422
    ): ResponseInterface {
        return $this->error($response, $message, $status, $errors);
    }

    /**
     * Konflikt (409) – nesie voliteľný kontext (`conflict`, `lock`, …).
     *
     * @param array<string, mixed> $context
     */
    public function conflict(
        ResponseInterface $response,
        string $message,
        array $context = [],
        int $status = 409
    ): ResponseInterface {
        $payload = array_merge([
            'success' => false,
            'error' => $message,
        ], $context);

        return $this->write($response, $payload, $status);
    }

    /**
     * Vlastný JSON payload (auth/legacy endpointy s plochým obalom).
     *
     * @param array<int|string, mixed> $payload
     */
    public function respond(
        ResponseInterface $response,
        array $payload,
        int $status = 200
    ): ResponseInterface {
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
