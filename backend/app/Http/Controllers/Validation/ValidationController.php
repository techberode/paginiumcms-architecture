<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Validation;

use PaginiumCMS\Core\Validation\ValidationRules;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
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
    public function __construct(
        private JsonResponder $json,
        private PasswordPolicyInterface $passwordPolicy
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->allRules());
    }

    /**
     * @param array<string, string> $args
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $context = (string) ($args['context'] ?? '');
        $rules = $this->allRules()[$context] ?? null;

        if ($rules === null) {
            return $this->json->error($response, 'Neznámy validačný kontext', 404);
        }

        return $this->json->success($response, $rules);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function allRules(): array
    {
        $all = ValidationRules::all();
        $policy = ValidationRules::passwordPolicyFrom($this->passwordPolicy);
        $all['password']['policy'] = $policy;
        $all['password']['rules']['password'] = [
            'required',
            'string',
            sprintf('min:%d', $policy['minLength']),
            sprintf('max:%d', $policy['maxLength']),
        ];
        $all['password']['rules']['passwordConfirm'] = [
            'required',
            'string',
            sprintf('min:%d', $policy['minLength']),
            sprintf('max:%d', $policy['maxLength']),
        ];

        return $all;
    }
}
