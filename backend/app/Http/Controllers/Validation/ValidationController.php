<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Validation;

use PaginiumCMS\Core\Validation\ValidationRules;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * === Controller: ValidationController ===
 * Verejný export zdieľaných validačných pravidiel (Iterácia 4).
 *
 * GET /api/validation/rules           – všetky sady pravidiel
 * GET /api/validation/rules/{context} – jedna sada (login, password, content, user)
 */
final class ValidationController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, [
            'success' => true,
            'data' => ValidationRules::all(),
        ]);
    }

    /**
     * @param array<string, string> $args
 */public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $context = (string) ($args['context'] ?? '');
        $rules = ValidationRules::for($context);

        if ($rules === null) {
            return $this->json($response, [
                'success' => false,
                'error' => 'Neznámy validačný kontext',
            ], 404);
        }

        return $this->json($response, [
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * @param array<int|string, mixed> $payload
 */private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
