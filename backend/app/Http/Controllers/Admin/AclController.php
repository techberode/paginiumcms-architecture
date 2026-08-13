<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Security\SecurityLogger;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\AclRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AclController
{
    public function __construct(
        private AclRepository $acl,
        private SecurityLogger $securityLogger,
        private JsonResponder $json
    ) {
    }

    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'enabled' => $this->acl->isEnabled(),
            'rules' => $this->acl->rules(),
        ]);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $enabled = ($data['enabled'] ?? false) === true;
        $rules = is_array($data['rules'] ?? null) ? $data['rules'] : [];

        $saved = $this->acl->save($enabled, $rules);

        $user = $request->getAttribute('user');
        if ($user instanceof User) {
            $this->securityLogger->logSettingsChange($user, 'acl');
        }

        return $this->json->success($response, $saved, 200, 'ACL rules saved');
    }
}
