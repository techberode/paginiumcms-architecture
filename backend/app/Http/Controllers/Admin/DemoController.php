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

    public function reset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User || !in_array('SUPER_ADMIN', $user->getRoles(), true)) {
            return $this->json->error($response, 'Len SUPER_ADMIN môže resetovať demo úložisko', 403);
        }

        try {
            $result = $this->demoStorage->reset();
        } catch (RuntimeException $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }

        return $this->json->success($response, $result, 200, 'Demo úložisko bolo obnovené');
    }
}
