<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\CodePolicy\Exceptions\CodePolicyViolationException;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadPolicyException;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Http\Extensions\Contracts\PluginManagerInterface;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkIdsParser;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Admin API for external extensions (It.15).
 */
final class ExtensionsController
{
    public function __construct(
        private PluginManagerInterface $plugins,
        private JsonResponder $json,
        private UploadPolicyEngine $uploadPolicy,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'extensions' => $this->plugins->list(),
        ]);
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'ZIP súbor je povinný', 400);
        }

        $clientName = $file->getClientFilename() ?? 'extension.zip';
        $uploadSize = (int) ($file->getSize() ?? 0);
        $tempPath = sys_get_temp_dir() . '/pag_extension_upload_' . uniqid('', true) . '.zip';
        $file->moveTo($tempPath);

        try {
            $this->uploadPolicy->enforceArchive(
                UploadSurfaceRegistry::SURFACE_EXTENSION_IMPORT,
                $clientName,
                $uploadSize > 0 ? $uploadSize : (int) filesize($tempPath),
                $tempPath,
                $this->resolveUserId($request)
            );
            $extension = $this->plugins->import($tempPath);

            return $this->json->success($response, $extension, 201, 'Rozšírenie bolo importované');
        } catch (UploadPolicyException $exception) {
            return $this->json->error($response, $exception->getMessage(), 413);
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
    public function enable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');

        try {
            $this->plugins->enable($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['id' => $id, 'enabled' => true]);
    }

    /**
     * @param array<string, string> $args
     */
    public function disable(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');

        try {
            $this->plugins->disable($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['id' => $id, 'enabled' => false]);
    }

    /**
     * @param array<string, string> $args
     */
    public function uninstall(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (string) ($args['id'] ?? '');

        try {
            $this->plugins->uninstall($id);
        } catch (RuntimeException $exception) {
            return $this->json->error($response, $exception->getMessage(), 404);
        }

        return $this->json->success($response, ['id' => $id, 'removed' => true]);
    }

    public function bulkUninstall(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = BulkIdsParser::fromRequest($request);
        if ($ids === []) {
            return $this->json->error($response, 'No extensions selected', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            try {
                $this->plugins->uninstall($id);
                $batch->addSuccess($id);
            } catch (RuntimeException $exception) {
                $batch->addFailure($id, $exception->getMessage());
            }
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Extensions uninstalled');
    }

    private function resolveUserId(ServerRequestInterface $request): ?string
    {
        $user = $request->getAttribute('user');

        return $user instanceof User ? $user->getId() : null;
    }
}
