<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Validation;

use PaginiumCMS\Core\Validation\ValidationRules;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * === Controller: ValidationController ===
 * Verejný export zdieľaných validačných pravidiel (Iterácia 4).
 *
 * GET /api/validation/rules           – všetky sady pravidiel
 * GET /api/validation/rules/{context} – jedna sada (login, password, content, user)
 */
final class ValidationController
{
    public function __construct(private JsonResponder $json)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, ValidationRules::all());
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $context = (string) ($args['context'] ?? '');
        $rules = ValidationRules::for($context);

        if ($rules === null) {
            return $this->json->error($response, 'Neznámy validačný kontext', 404);
        }

        return $this->json->success($response, $rules);
    }
}
