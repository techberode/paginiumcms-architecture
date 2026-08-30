<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Themes\Services\ThemeManager;
use PaginiumCMS\Http\Themes\Services\ThemeRuntimeService;
use PaginiumCMS\Http\Themes\Services\ThemeStarterPackageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Admin API for theme package import (It.67b).
 */
final class ThemesController
{
    public function __construct(
        private ThemeManager $themes,
        private ThemeStarterPackageService $starterPackages,
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'themes' => $this->themes->list(),
            'activeThemeId' => $this->themes->getActiveThemeId(),
            'coreThemeId' => ThemeRuntimeService::CORE_THEME_ID,
        ]);
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'ZIP file is required', 400);
        }

        $tempPath = sys_get_temp_dir() . '/pag_theme_upload_' . uniqid('', true) . '.zip';
        $file->moveTo($tempPath);

        try {
            $theme = $this->themes->import($tempPath);

            return $this->json->success($response, $theme, 201, 'Theme package imported');
        } catch (CodePolicyViolationException $exception) {
            return $this->json->validation($response, $exception->getMessage(), $exception->getErrors());
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * @param array<string, string> $args
     */
    public function uninstall(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');

        try {
            $this->themes->uninstall($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['id' => $id, 'removed' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function activate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');

        try {
            $result = $this->themes->activate($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        return $this->json->success($response, $result, 200, 'Theme activated');
    }

    public function deactivate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $result = $this->themes->deactivate();
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 422);
        }

        return $this->json->success($response, $result, 200, 'Theme deactivated');
    }

    /**
     * @param array<string, string> $args
     */
    public function downloadStarter(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');

        try {
            $zipPath = $this->starterPackages->buildZipPath($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        $stream = fopen($zipPath, 'rb');
        if ($stream === false) {
            @unlink($zipPath);

            return $this->json->error($response, 'Unable to read starter package.', 500);
        }

        $body = $response->getBody();
        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) {
                break;
            }
            $body->write($chunk);
        }
        fclose($stream);
        @unlink($zipPath);

        $filename = $id . '-starter.zip';

        return $response
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . addslashes($filename) . '"')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }
}
