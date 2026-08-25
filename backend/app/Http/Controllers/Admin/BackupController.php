<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Backup\Contracts\BackupInterface;
use PaginiumCMS\Core\Backup\Models\BackupMetadata;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\BulkBatchResult;
use PaginiumCMS\Http\Support\BulkOperationLimits;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Support\FileHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class BackupController
{
    public function __construct(
        private BackupInterface $backup,
        private JsonResponder $json,
        private SettingsRepositoryInterface $settings,
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
        $data = RequestJsonBody::decode($request);
        $name = is_array($data) ? ($data['name'] ?? '') : '';

        if ($name === '') {
            return $this->json->error($response, 'Názov zálohy je povinný', 400);
        }

        try {
            $includes = is_array($data) && isset($data['includes']) && is_array($data['includes'])
                ? $data['includes']
                : ['content', 'config', 'data'];
            $backup = $this->backup->create((string) $name, ['includes' => $includes]);

            return $this->json->success($response, $backup->jsonSerialize(), 201);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function importBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;

        if (!$file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            return $this->json->error($response, 'ZIP súbor je povinný', 400);
        }

        $maxKb = max(1024, (int) ($this->settings->group('uploadSecurity')['backupImportMaxSizeKb'] ?? 102400));
        $maxBytes = $maxKb * 1024;
        $uploadSize = $file->getSize();
        if ($uploadSize !== null && $uploadSize > $maxBytes) {
            return $this->json->error(
                $response,
                sprintf('Backup ZIP exceeds maximum import size (%d KB).', $maxKb),
                413
            );
        }

        $tempPath = sys_get_temp_dir() . '/paginium_import_' . uniqid('', true) . '.zip';
        $file->moveTo($tempPath);

        $parsedBody = $request->getParsedBody();
        $name = is_array($parsedBody) ? trim((string) ($parsedBody['name'] ?? '')) : '';
        if ($name === '') {
            $clientName = $file->getClientFilename() ?? 'imported-backup';
            $name = pathinfo($clientName, PATHINFO_FILENAME) ?: 'imported-backup';
        }

        try {
            $backup = $this->backup->registerArchive($tempPath, $name);

            return $this->json->success(
                $response,
                $backup->jsonSerialize(),
                201,
                'Záloha bola importovaná'
            );
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 400);
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function downloadBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');

        try {
            $metadata = $this->backup->getBackup($id);
            if ($metadata === null) {
                return $this->json->error($response, 'Záloha nebola nájdená', 404);
            }

            $filePath = $this->backup->exportBackup($id);
            $filename = basename($filePath);

            $response->getBody()->write(FileHelper::read($filePath));

            $response = $response
                ->withHeader('Content-Type', 'application/zip')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->withHeader('Content-Length', (string) filesize($filePath));

            if ($metadata->getSha256() !== '') {
                $response = $response->withHeader('X-Backup-SHA256', $metadata->getSha256());
            }

            return $response;
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 404);
        }
    }

    public function verifyBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $id = (string) $request->getAttribute('id');

        try {
            $result = $this->backup->verifyIntegrity($id);

            return $this->json->success($response, $result);
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

    public function bulkDeleteBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = $this->normalizeIds($request);
        if ($ids === []) {
            return $this->json->error($response, 'Vyžaduje sa aspoň jedno ID', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            if ($this->backup->deleteBackup($id)) {
                $batch->addSuccess($id);
            } else {
                $batch->addFailure($id, 'Vymazanie zlyhalo');
            }
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Hromadné mazanie dokončené');
    }

    public function bulkRestoreBackups(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = $this->normalizeIds($request);
        if ($ids === []) {
            return $this->json->error($response, 'Vyžaduje sa aspoň jedno ID', 400);
        }

        $batch = new BulkBatchResult();
        foreach ($ids as $id) {
            try {
                if ($this->backup->restore($id)) {
                    $batch->addSuccess($id);
                } else {
                    $batch->addFailure($id, 'Obnova zlyhala');
                }
            } catch (\Exception $e) {
                $batch->addFailure($id, $e->getMessage());
            }
        }

        return $this->json->success($response, $batch->toArray(), 200, 'Hromadná obnova dokončená');
    }

    public function getSchedule(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->backup->getScheduleInfo());
    }

    public function scheduleBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        if (array_key_exists('enabled', $data) && $data['enabled'] === false) {
            $this->backup->clearSchedule();

            return $this->json->success($response, ['enabled' => false], 200, 'Backup schedule disabled');
        }

        $interval = (string) ($data['interval'] ?? '');
        if (!in_array($interval, ['daily', 'weekly', 'monthly'], true)) {
            return $this->json->error($response, 'Interval must be daily, weekly or monthly', 422);
        }

        $keep = max(1, min(365, (int) ($data['keep'] ?? 7)));
        $this->backup->scheduleBackup($interval, $keep);

        return $this->json->success($response, $this->backup->getScheduleInfo(), 200, 'Backup schedule saved');
    }

    /**
     * @return list<string>
     */
    private function normalizeIds(ServerRequestInterface $request): array
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data) || !isset($data['ids']) || !is_array($data['ids'])) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($id): string => is_string($id) ? trim($id) : '', $data['ids']),
            static fn (string $id): bool => $id !== ''
        ));
    }
}
