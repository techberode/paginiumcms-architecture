<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\FlatFile\Services\ContentImportService;
use PaginiumCMS\Core\Import\ContentImportSourceRegistry;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadPolicyException;
use PaginiumCMS\Core\Security\Upload\UploadSurfaceRegistry;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Admin CMS content migration (WordPress, Grav, Jekyll, Hugo, Ghost).
 */
final class ContentMigrationController
{
    public function __construct(
        private ContentImportSourceRegistry $sources,
        private ContentImportService $import,
        private JsonResponder $json,
        private UploadPolicyEngine $uploadPolicy,
    ) {
    }

    public function listSources(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'sources' => $this->sources->listSources(),
        ]);
    }

    public function import(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $format = strtolower(trim((string) ($params['format'] ?? 'auto')));
        $run = filter_var($params['run'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'Upload file is required', 400);
        }

        $clientName = $file->getClientFilename() ?? 'import.zip';
        $tempPath = sys_get_temp_dir() . '/paginium_cms_upload_' . bin2hex(random_bytes(8)) . '_' . basename($clientName);
        $file->moveTo($tempPath);

        $uploadSize = (int) ($file->getSize() ?? 0);
        if ($uploadSize <= 0) {
            $uploadSize = (int) filesize($tempPath);
        }

        $extension = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        try {
            if ($extension === 'zip') {
                $this->uploadPolicy->enforceArchive(
                    UploadSurfaceRegistry::SURFACE_CMS_MIGRATION,
                    $clientName,
                    $uploadSize,
                    $tempPath,
                    null
                );
            } else {
                $binary = (string) file_get_contents($tempPath);
                $this->uploadPolicy->enforceBinary(
                    UploadSurfaceRegistry::SURFACE_CMS_MIGRATION,
                    $clientName,
                    $binary,
                    $extension === 'json' ? 'application/json' : 'application/xml',
                    null
                );
            }
        } catch (UploadPolicyException $exception) {
            @unlink($tempPath);

            return $this->json->error($response, $exception->getMessage(), 422);
        }

        $result = $this->import->importFromUploadedFile($format, $tempPath, $clientName, !$run);

        return $this->json->success($response, $result->toArray());
    }
}
