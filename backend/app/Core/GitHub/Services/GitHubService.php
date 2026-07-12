<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\GitHub\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;

class GitHubService
{
    private string $token;
    private string $repo;
    private string $branch;
    private bool $enabled;
    private bool $autoSync;
    private FileReaderInterface $reader;
    private FileWriterInterface $writer;
    private string $contentPath;
    private string $apiUrl = 'https://api.github.com';

    public function __construct(
        FileReaderInterface $reader,
        FileWriterInterface $writer,
        array $config = []
    ) {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->token = $config['token'] ?? '';
        $this->repo = $config['repo'] ?? '';
        $this->branch = $config['branch'] ?? 'main';
        $this->enabled = $config['enabled'] ?? false;
        $this->autoSync = $config['auto_sync'] ?? false;
        $this->contentPath = $config['content_path'] ?? 'content';
    }

    /**
     * Exportuje obsah do GitHub repozitára.
     */
    public function export(string $message = 'Export obsahu', string $path = null): array
    {
        $path = $path ?? $this->contentPath;
        $result = ['success' => true, 'files' => 0, 'errors' => [], 'skipped' => 0];

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'GitHub synchronizácia nie je nakonfigurovaná'];
        }

        try {
            $files = $this->reader->listFiles($path, '*.*');
        } catch (FlatFileException $e) {
            return ['success' => false, 'error' => 'Nepodarilo sa načítať obsah: ' . $e->getMessage()];
        }

        foreach ($files as $file) {
            try {
                $content = $this->reader->read($path . '/' . $file);
                $this->uploadFile($path . '/' . $file, $content, $message);
                $result['files']++;
            } catch (\Exception $e) {
                $result['errors'][] = $file . ': ' . $e->getMessage();
                $result['success'] = false;
            }
        }

        return $result;
    }

    /**
     * Importuje obsah z GitHub repozitára.
     */
    public function import(string $path = null): array
    {
        $path = $path ?? $this->contentPath;
        $result = ['success' => true, 'files' => 0, 'errors' => [], 'skipped' => 0];

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'GitHub synchronizácia nie je nakonfigurovaná'];
        }

        try {
            $files = $this->getRepoContents($path);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Nepodarilo sa načítať obsah z GitHub: ' . $e->getMessage()];
        }

        foreach ($files as $file) {
            if ($file['type'] === 'dir') {
                continue;
            }

            try {
                $content = $this->getFileContent($file['path']);
                if ($content !== null) {
                    $this->writer->write($file['path'], $content);
                    $result['files']++;
                } else {
                    $result['skipped']++;
                }
            } catch (\Exception $e) {
                $result['errors'][] = $file['path'] . ': ' . $e->getMessage();
                $result['success'] = false;
            }
        }

        return $result;
    }

    /**
     * Získa stav synchronizácie.
     */
    public function getStatus(): array
    {
        return [
            'enabled' => $this->enabled,
            'repo' => $this->repo,
            'branch' => $this->branch,
            'auto_sync' => $this->autoSync,
            'configured' => $this->isConfigured(),
        ];
    }

    /**
     * Nastaví automatickú synchronizáciu.
     */
    public function setAutoSync(bool $enabled): void
    {
        $this->autoSync = $enabled;
        $this->updateConfig(['GITHUB_AUTO_SYNC' => $enabled ? 'true' : 'false']);
    }

    /**
     * Vykoná synchronizáciu – export + import.
     */
    public function sync(string $message = 'Synchronizácia obsahu'): array
    {
        $exportResult = $this->export($message);
        if (!$exportResult['success']) {
            $errors = $exportResult['errors'] ?? [];
            return [
                'success' => false,
                'error' => 'Export zlyhal: ' . implode(', ', $errors),
                'exported' => $exportResult['files'] ?? 0,
                'imported' => 0,
                'errors' => $errors,
            ];
        }

        $importResult = $this->import();
        if (!$importResult['success']) {
            $errors = $importResult['errors'] ?? [];
            return [
                'success' => false,
                'error' => 'Import zlyhal: ' . implode(', ', $errors),
                'exported' => $exportResult['files'] ?? 0,
                'imported' => 0,
                'errors' => $errors,
            ];
        }

        return [
            'success' => true,
            'exported' => $exportResult['files'] ?? 0,
            'imported' => $importResult['files'] ?? 0,
            'errors' => array_merge(
                $exportResult['errors'] ?? [],
                $importResult['errors'] ?? []
            ),
        ];
    }

    private function isConfigured(): bool
    {
        return $this->enabled && !empty($this->token) && !empty($this->repo);
    }

    private function uploadFile(string $path, string $content, string $message): void
    {
        $sha = $this->getFileSha($path);
        $data = [
            'message' => $message,
            'content' => base64_encode($content),
            'branch' => $this->branch,
        ];

        if ($sha) {
            $data['sha'] = $sha;
        }

        $url = $this->apiUrl . '/repos/' . $this->repo . '/contents/' . $path;
        $this->apiRequest($url, 'PUT', $data);
    }

    private function getFileSha(string $path): ?string
    {
        $url = $this->apiUrl . '/repos/' . $this->repo . '/contents/' . $path . '?ref=' . $this->branch;
        try {
            $response = $this->apiRequest($url, 'GET');
            return $response['sha'] ?? null;
        } catch (\Exception) {
            return null;
        }
    }

    private function getFileContent(string $path): ?string
    {
        $url = $this->apiUrl . '/repos/' . $this->repo . '/contents/' . $path . '?ref=' . $this->branch;
        $response = $this->apiRequest($url, 'GET');
        if (isset($response['content'])) {
            return base64_decode($response['content']);
        }
        return null;
    }

    private function getRepoContents(string $path): array
    {
        $url = $this->apiUrl . '/repos/' . $this->repo . '/contents/' . $path . '?ref=' . $this->branch;
        $response = $this->apiRequest($url, 'GET');
        return is_array($response) ? $response : [];
    }

    private function apiRequest(string $url, string $method = 'GET', array $data = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PaginiumCMS');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $this->token,
            'Accept: application/vnd.github.v3+json',
        ]);

        if ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
                curl_getinfo($ch, CURLOPT_HTTPHEADER) ?: [],
                ['Content-Type: application/json']
            ));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \Exception('GitHub API chyba: ' . $httpCode . ' - ' . $response);
        }

        return json_decode($response, true) ?? [];
    }

    private function updateConfig(array $values): void
    {
        $envPath = __DIR__ . '/../../../../.env';
        if (!file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        foreach ($values as $key => $value) {
            if (preg_match('/^' . $key . '=/m', $content)) {
                $content = preg_replace('/^' . $key . '=.*/m', $key . '=' . $value, $content);
            } else {
                $content .= "\n" . $key . '=' . $value;
            }
        }
        file_put_contents($envPath, $content);
    }
}
