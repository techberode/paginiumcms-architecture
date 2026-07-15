<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\GitHub\Services\GitHubService;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GitHubController
{
    public function __construct(
        private GitHubService $gitHubService
    ) {
    }

    public function status(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->jsonSuccess($response, $this->gitHubService->getStatus());
    }

    public function export(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        $message = is_array($data) ? (string) ($data['message'] ?? 'Export content') : 'Export content';

        $result = $this->gitHubService->export($message);

        return $this->jsonSuccess($response, $result, null, ($result['success'] ?? false) ? 200 : 400);
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->gitHubService->import();

        return $this->jsonSuccess($response, $result, null, ($result['success'] ?? false) ? 200 : 400);
    }

    public function sync(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        $message = is_array($data) ? (string) ($data['message'] ?? 'Content sync') : 'Content sync';

        $result = $this->gitHubService->sync($message);

        return $this->jsonSuccess($response, $result, null, ($result['success'] ?? false) ? 200 : 400);
    }

    public function setAutoSync(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !array_key_exists('enabled', $data)) {
            return $this->jsonError($response, Lang::get('invalid_payload', [], 'github'), 400);
        }

        $this->gitHubService->setAutoSync((bool) $data['enabled']);

        return $this->jsonSuccess($response, $this->gitHubService->getStatus(), Lang::get('updated', [], 'github'));
    }

    private function jsonSuccess(ResponseInterface $response, mixed $data, ?string $message = null, int $status = 200): ResponseInterface
    {
        $payload = ['success' => (bool) ($data['success'] ?? true), 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
