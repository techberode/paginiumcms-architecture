<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PaginiumCMS\Support\JsonHelper;

/**
 * Admin code editor API (Iteration 14).
 */
class CodeEditorController
{
    public function __construct(private CodeEditorManager $editor)
    {
    }

    public function listFiles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $directory = (string) ($params['directory'] ?? '');

        try {
            $files = $this->editor->listFiles($directory);

            return $this->json($response, [
                'success' => true,
                'data' => $files,
                'directory' => $directory !== '' ? $directory : $this->editor->getDefaultDirectory(),
            ]);
        } catch (\Throwable $e) {
            return $this->error($response, $e);
        }
    }

    public function getFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = (string) ($request->getQueryParams()['path'] ?? '');
        if ($path === '') {
            return $this->json($response, ['success' => false, 'error' => 'Path is required'], 400);
        }

        try {
            $content = $this->editor->readFile($path);
            $info = $this->editor->getFileInfo($path);

            return $this->json($response, [
                'success' => true,
                'data' => [
                    'content' => $content,
                    'path' => $path,
                    'language' => $info['language'],
                    'info' => $info,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->error($response, $e);
        }
    }

    public function saveFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json($response, ['success' => false, 'error' => 'Invalid JSON body'], 400);
        }

        $path = (string) ($data['path'] ?? '');
        $content = (string) ($data['content'] ?? '');
        if ($path === '') {
            return $this->json($response, ['success' => false, 'error' => 'Path is required'], 400);
        }

        try {
            $this->editor->writeFile($path, $content);

            return $this->json($response, [
                'success' => true,
                'message' => 'File saved successfully',
            ]);
        } catch (\Throwable $e) {
            return $this->error($response, $e);
        }
    }

    public function getBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = (string) ($request->getQueryParams()['path'] ?? '');

        try {
            return $this->json($response, [
                'success' => true,
                'data' => $this->editor->getBackups($path),
            ]);
        } catch (\Throwable $e) {
            return $this->error($response, $e);
        }
    }

    private function error(ResponseInterface $response, \Throwable $e): ResponseInterface
    {
        if ($e instanceof CodePolicyViolationException) {
            return $this->json($response, [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ], 422);
        }

        return $this->json($response, [
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }

    /**
     * @param array<int|string, mixed> $payload
 */private function json(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(JsonHelper::encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
