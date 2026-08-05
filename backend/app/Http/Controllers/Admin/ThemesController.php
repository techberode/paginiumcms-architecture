<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Http\Themes\Services\ThemeManager;
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
        private JsonResponder $json,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'themes' => $this->themes->list(),
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
}
