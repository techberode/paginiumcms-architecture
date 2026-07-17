<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\FileHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BackupController
{
    public function __construct(
        private BackupInterface $backup,
        private JsonResponder $json
    ) {
    }

    public function listBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $backups = array_map(
            static fn (BackupMetadata $backup) => $backup->jsonSerialize(),
            $this->backup->listBackups()
        );

        return $this->json->success($response, $backups);
    }

    public function createBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        $name = is_array($data) ? ($data['name'] ?? '') : '';

        if ($name === '') {
            return $this->json->error($response, 'Názov zálohy je povinný', 400);
        }

        try {
            $backup = $this->backup->create((string) $name);

            return $this->json->success($response, $backup->jsonSerialize(), 201);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function downloadBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');

        try {
            $filePath = $this->backup->exportBackup($id);
            $filename = basename($filePath);

            $response->getBody()->write(FileHelper::read($filePath));

            return $response
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) filesize($filePath));
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }
    }

    public function restoreBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');

        try {
            $result = $this->backup->restore($id);

            if (!$result) {
                return $this->json->error($response, 'Obnova zálohy zlyhala', 500);
            }

            return $this->json->success($response, null, 200, 'Záloha bola obnovená');
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function deleteBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');

        try {
            $result = $this->backup->deleteBackup($id);

            if (!$result) {
                return $this->json->error($response, 'Vymazanie zálohy zlyhalo', 500);
            }

            return $this->json->success($response, null, 200, 'Záloha bola vymazaná');
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }
}
