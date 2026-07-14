<?php
// backend/app/Http/Controllers/Admin/CodeEditorController.php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodeEditor\Services\CodeEditorManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class CodeEditorController
{
    private CodeEditorManager $editor;

    public function __construct(CodeEditorManager $editor)
    {
        $this->editor = $editor;
    }

    public function listFiles(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $directory = $params['directory'] ?? '';

        try {
            $files = $this->editor->listFiles($directory);
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $files
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $path = $params['path'] ?? '';

        if (empty($path)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Path is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $content = $this->editor->readFile($path);
            $info = $this->editor->getFileInfo($path);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'content' => $content,
                    'path' => $path,
                    'language' => $info['language'],
                    'info' => $info
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function saveFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        $path = $data['path'] ?? '';
        $content = $data['content'] ?? '';

        if (empty($path)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Path is required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->editor->writeFile($path, $content);
            
            $response->getBody()->write(json_encode([
                'success' => $result,
                'message' => $result ? 'File saved successfully' : 'Failed to save file'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function getBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $path = $params['path'] ?? '';

        try {
            $backups = $this->editor->getBackups($path);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $backups
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
