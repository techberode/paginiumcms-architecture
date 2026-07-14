<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

class BackupController
{
    private BackupInterface $backup;

    public function __construct(BackupInterface $backup)
    {
        $this->backup = $backup;
    }

    public function listBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $backups = $this->backup->listBackups();

        $response->getBody()->write(json_encode([
            'success' => true,
            'backups' => array_map(function (BackupMetadata $backup) {
                return $backup->jsonSerialize();
            }, $backups),
        ], JSON_PRETTY_PRINT));

        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        $name = $data['name'] ?? '';

        if (empty($name)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Názov zálohy je povinný',
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $backup = $this->backup->create($name);

            $response->getBody()->write(json_encode([
                'success' => true,
                'backup' => $backup->jsonSerialize(),
            ], JSON_PRETTY_PRINT));

            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function downloadBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $request->getAttribute('id');

        try {
            $filePath = $this->backup->exportBackup($id);
            $filename = basename($filePath);

            $response->getBody()->write(file_get_contents($filePath));
            return $response
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string)filesize($filePath));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }

    public function restoreBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $request->getAttribute('id');

        try {
            $result = $this->backup->restore($id);

            $response->getBody()->write(json_encode([
                'success' => $result,
                'message' => $result ? 'Záloha bola obnovená' : 'Obnova zálohy zlyhala',
            ], JSON_PRETTY_PRINT));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function deleteBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = $request->getAttribute('id');

        try {
            $result = $this->backup->deleteBackup($id);

            $response->getBody()->write(json_encode([
                'success' => $result,
                'message' => $result ? 'Záloha bola vymazaná' : 'Vymazanie zálohy zlyhalo',
            ], JSON_PRETTY_PRINT));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
