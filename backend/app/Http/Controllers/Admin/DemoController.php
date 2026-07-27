<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Demo\Services\DemoStorageService;
use PaginiumCMS\Modules\Security\Models\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class DemoController
{
    public function __construct(
        private DemoStorageService $demoStorage,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->demoStorage->status());
    }

    /**
     * Public demo info — no secrets, only when DEMO_MODE=true.
     */
    public function publicInfo(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->demoStorage->isEnabled()) {
            return $this->json->success($response, ['enabled' => false]);
        }

        $status = $this->demoStorage->status();

        return $this->json->success($response, [
            'enabled' => true,
            'auto_reset_minutes' => $status['auto_reset_minutes'],
            'last_reset_at' => $status['last_reset_at'],
            'next_reset_at' => $status['next_reset_at'],
            'seconds_until_reset' => $status['seconds_until_reset'],
            'isolated' => true,
        ]);
    }

    public function reset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            return $this->json->error($response, 'Neautorizovaný prístup', 403);
        }

        $roles = $user->getRoles();
        if (!in_array('ADMIN', $roles, true) && !in_array('SUPER_ADMIN', $roles, true)) {
            return $this->json->error($response, 'Len ADMIN môže resetovať demo úložisko', 403);
        }

        try {
            $result = $this->demoStorage->reset();
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }

        return $this->json->success($response, $result, 200, 'Demo úložisko bolo obnovené');
    }
}
