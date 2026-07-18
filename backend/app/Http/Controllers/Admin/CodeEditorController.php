<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin code editor API (Iteration 14).
 */
class CodeEditorController
{
    public function __construct(
        protected CodeEditorManager $editor,
        protected JsonResponder $json
    ) {
    }

    public function listFiles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $directory = (string) ($params['directory'] ?? '');

        try {
            $files = $this->editor->listFiles($directory);
            $listedDirectory = $directory !== '' ? $directory : 'all';

            return $this->json->respond($response, [
                'success' => true,
                'data' => $files,
                'directory' => $listedDirectory,
                'directories' => $this->editor->getAllowedDirectories(),
            ]);
        } catch (\Throwable $e) {
            return $this->respondThrowable($response, $e);
        }
    }

    public function listDirectories(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'directories' => $this->editor->getAllowedDirectories(),
            'default' => $this->editor->getDefaultDirectory(),
        ]);
    }

    public function getFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = (string) ($request->getQueryParams()['path'] ?? '');
        if ($path === '') {
            return $this->json->error($response, 'Path is required', 400);
        }

        try {
            $content = $this->editor->readFile($path);
            $info = $this->editor->getFileInfo($path);

            return $this->json->success($response, [
                'content' => $content,
                'path' => $path,
                'language' => $info['language'],
                'info' => $info,
            ]);
        } catch (\Throwable $e) {
            return $this->respondThrowable($response, $e);
        }
    }

    public function saveFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $path = (string) ($data['path'] ?? '');
        $content = (string) ($data['content'] ?? '');
        if ($path === '') {
            return $this->json->error($response, 'Path is required', 400);
        }

        try {
            $this->editor->writeFile($path, $content);

            return $this->json->success($response, null, 200, 'File saved successfully');
        } catch (\Throwable $e) {
            return $this->respondThrowable($response, $e);
        }
    }

    public function getBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = (string) ($request->getQueryParams()['path'] ?? '');

        try {
            return $this->json->success($response, $this->editor->getBackups($path));
        } catch (\Throwable $e) {
            return $this->respondThrowable($response, $e);
        }
    }

    protected function respondThrowable(ResponseInterface $response, \Throwable $e): ResponseInterface
    {
        if ($e instanceof CodePolicyViolationException) {
            return $this->json->validation($response, $e->getMessage(), $e->getErrors());
        }

        return $this->json->error($response, $e->getMessage(), 500);
    }
}
