<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GitHubController
{
    public function __construct(
        private GitHubService $gitHubService,
        private JsonResponder $json
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->gitHubService->getStatus());
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        $message = is_array($data) ? (string) ($data['message'] ?? 'Export content') : 'Export content';

        $result = $this->gitHubService->export($message);

        return $this->json->respond($response, [
            'success' => (bool) ($result['success'] ?? true),
            'data' => $result,
        ], ($result['success'] ?? false) ? 200 : 400);
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->gitHubService->import();

        return $this->json->respond($response, [
            'success' => (bool) ($result['success'] ?? true),
            'data' => $result,
        ], ($result['success'] ?? false) ? 200 : 400);
    }

    public function sync(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        $message = is_array($data) ? (string) ($data['message'] ?? 'Content sync') : 'Content sync';

        $result = $this->gitHubService->sync($message);

        return $this->json->respond($response, [
            'success' => (bool) ($result['success'] ?? true),
            'data' => $result,
        ], ($result['success'] ?? false) ? 200 : 400);
    }

    public function setAutoSync(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data) || !array_key_exists('enabled', $data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'github'), 400);
        }

        $this->gitHubService->setAutoSync((bool) $data['enabled']);

        return $this->json->success($response, $this->gitHubService->getStatus(), 200, Lang::get('updated', [], 'github'));
    }
}
