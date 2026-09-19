<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use InvalidArgumentException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Modules\Security\Services\RegistrationOptionsStore;
use PaginiumCMS\Modules\Security\Services\RoleRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RegistrationOptionsController
{
    public function __construct(
        private RegistrationOptionsStore $options,
        private RoleRepository $roles,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'options' => $this->options->seedIfEmpty(),
            'roles' => $this->pickerRoles(),
        ]);
    }

    public function save(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = RequestJsonBody::decode($request) ?? [];
        $raw = $body['options'] ?? [];
        if (!is_array($raw)) {
            return $this->json->validation($response, 'Validation failed', ['options' => 'Options must be a list.']);
        }

        try {
            $options = $this->options->save($raw);
        } catch (InvalidArgumentException $exception) {
            return $this->json->validation($response, 'Validation failed', ['options' => $exception->getMessage()]);
        }

        return $this->json->success($response, ['options' => $options]);
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function pickerRoles(): array
    {
        $out = [];
        foreach ($this->roles->list() as $role) {
            if (in_array($role['id'], ['ADMIN', 'SUPER_ADMIN'], true)) {
                continue;
            }
            $out[] = [
                'id' => $role['id'],
                'name' => $role['name'] !== '' ? $role['name'] : $role['id'],
            ];
        }

        return $out;
    }
}
