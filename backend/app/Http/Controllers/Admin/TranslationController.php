<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\I18n\Contracts\TranslationFileManagerInterface;
use PaginiumCMS\Core\I18n\Exception\TranslationPolicyViolationException;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Light translation file editor API (It.18d) — Admin + 2FA, no Developer Mode.
 */
final class TranslationController
{
    public function __construct(
        private TranslationFileManagerInterface $translations,
        private JsonResponder $json
    ) {
    }

    public function catalog(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json->success($response, $this->translations->listCatalog());
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function getFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = (string) ($request->getQueryParams()['path'] ?? '');
        if ($path === '') {
            return $this->json->error($response, 'Path is required', 400);
        }

        try {
            $info = $this->translations->getFileInfo($path);

            return $this->json->success($response, [
                'path' => $path,
                'content' => $this->translations->readFile($path),
                'info' => $info,
                'language' => $info['language'],
            ]);
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function saveFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $path = (string) ($data['path'] ?? '');
        $content = (string) ($data['content'] ?? '');
        if ($path === '') {
            return $this->json->error($response, 'Path is required', 400);
        }

        try {
            $this->translations->writeFile($path, $content);

            return $this->json->success($response, [
                'path' => $path,
                'info' => $this->translations->getFileInfo($path),
            ], 200, 'Translation file saved');
        } catch (TranslationPolicyViolationException $e) {
            return $this->json->respond($response, [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $e->getErrors(),
                'rejected_path' => $e->getRejectedPath(),
            ], 422);
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function validateFile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $path = (string) ($data['path'] ?? '');
        $content = (string) ($data['content'] ?? '');
        if ($path === '') {
            return $this->json->error($response, 'Path is required', 400);
        }

        try {
            $errors = $this->translations->validateContent($path, $content);

            return $this->json->success($response, [
                'path' => $path,
                'valid' => $errors === [],
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function getBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = (string) ($request->getQueryParams()['path'] ?? '');
        if ($path === '') {
            return $this->json->error($response, 'Path is required', 400);
        }

        try {
            return $this->json->success($response, [
                'path' => $path,
                'backups' => $this->translations->getBackups($path),
            ]);
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function restoreBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $path = (string) ($data['path'] ?? '');
        $backupFile = (string) ($data['backup_file'] ?? '');
        if ($path === '' || $backupFile === '') {
            return $this->json->error($response, 'Path and backup_file are required', 400);
        }

        try {
            $this->translations->restoreBackup($path, $backupFile);

            return $this->json->success($response, [
                'path' => $path,
                'content' => $this->translations->readFile($path),
            ], 200, 'Backup restored');
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function listLocales(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json->success($response, [
                'locales' => $this->translations->listLocales(),
            ]);
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function createLocale(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $code = strtolower(trim((string) ($data['code'] ?? '')));
        $label = trim((string) ($data['label'] ?? ''));
        $copyFrom = strtolower(trim((string) ($data['copy_from'] ?? 'sk')));

        if ($code === '') {
            return $this->json->error($response, 'Locale code is required', 400);
        }

        try {
            $result = $this->translations->createLocale($code, $label, $copyFrom);

            return $this->json->success($response, $result, 201, 'Locale created');
        } catch (\Throwable $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        }
    }
}
