<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use RuntimeException;

/**
 * Isolated demo flat-file storage under `storage/app/demo/` (Iteration 13).
 * When DEMO_MODE is on, FileValidator already points at the demo tree — reset re-seeds the live CMS data.
 */
final class DemoStorageService
{
    private const LAST_RESET_FILE = '.meta/last-reset.json';

    private string $demoBasePath;
    private string $productionBasePath;

    public function __construct(
        private DemoMode $demoMode,
        FileReaderInterface $reader
    ) {
        $activeBasePath = rtrim($reader->getBasePath(), '/');

        if ($this->demoMode->isEnabled()) {
            $this->demoBasePath = $activeBasePath;
            $this->productionBasePath = dirname($activeBasePath) . '/content';
        } else {
            $this->productionBasePath = $activeBasePath;
            $this->demoBasePath = dirname($activeBasePath) . '/demo';
        }
    }

    public function isEnabled(): bool
    {
        return $this->demoMode->isEnabled();
    }

    public function demoBasePath(): string
    {
        return $this->demoBasePath;
    }

    public function contentBasePath(): string
    {
        return $this->productionBasePath;
    }

    /**
     * Seed demo snapshot on first boot when empty (skipped in APP_ENV=testing).
     */
    public function ensureSeeded(): void
    {
        if (!$this->demoMode->isEnabled()) {
            return;
        }

        $status = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        if ($status === 'testing') {
            return;
        }

        if ($this->needsSeed()) {
            $this->reset();
        }
    }

    /**
     * @return array{enabled: bool, storage_path: string, content_path: string, file_count: int, seeded: bool, auto_reset_minutes: int, last_reset_at: ?string, credentials: ?array{email: string, password: string}}
     */
    public function status(): array
    {
        $files = $this->listDemoFiles();
        $lastReset = $this->readLastResetTimestamp();

        return [
            'enabled' => $this->demoMode->isEnabled(),
            'storage_path' => $this->demoBasePath,
            'content_path' => $this->productionBasePath,
            'file_count' => count($files),
            'seeded' => $files !== [],
            'auto_reset_minutes' => $this->demoMode->autoResetMinutes(),
            'last_reset_at' => $lastReset !== null ? date('c', $lastReset) : null,
            'credentials' => $this->demoMode->isEnabled() ? [
                'email' => DemoFixtures::ADMIN_EMAIL,
                'password' => DemoFixtures::ADMIN_PASSWORD,
            ] : null,
        ];
    }

    /**
     * @return array{written: int, storage_path: string}
     */
    public function reset(): array
    {
        if (!$this->demoMode->isEnabled()) {
            throw new RuntimeException('Demo mode is disabled');
        }

        $this->assertIsolatedFromProduction();
        $this->clearDemoDirectory();

        $written = 0;
        foreach (DemoFixtures::seedFiles() as $relativePath => $contents) {
            $this->writeDemoFile($relativePath, $contents);
            ++$written;
        }

        $this->writeDemoFile('data/users/' . DemoFixtures::ADMIN_USER_ID . '.json', DemoFixtures::adminUserJson());
        ++$written;

        $this->writeLastResetTimestamp(time());

        return [
            'written' => $written,
            'storage_path' => $this->demoBasePath,
        ];
    }

    public function getLastResetTimestamp(): ?int
    {
        return $this->readLastResetTimestamp();
    }

    /**
     * @return list<string>
     */
    public function listDemoFiles(): array
    {
        if (!is_dir($this->demoBasePath)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->demoBasePath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    public function assertIsolatedFromProduction(): void
    {
        $demoReal = realpath($this->demoBasePath) ?: $this->demoBasePath;
        $contentReal = realpath($this->productionBasePath) ?: $this->productionBasePath;

        if ($demoReal === $contentReal) {
            throw new RuntimeException('Demo storage must not overlap production content path');
        }

        if (str_starts_with($demoReal, $contentReal . '/') || str_starts_with($contentReal, $demoReal . '/')) {
            throw new RuntimeException('Demo storage path must be isolated from production content');
        }
    }

    private function needsSeed(): bool
    {
        if (!is_dir($this->demoBasePath)) {
            return true;
        }

        $userFile = $this->demoBasePath . '/data/users/' . DemoFixtures::ADMIN_USER_ID . '.json';

        return !is_file($userFile) || $this->listDemoFiles() === [];
    }

    private function clearDemoDirectory(): void
    {
        if (!is_dir($this->demoBasePath)) {
            if (!mkdir($this->demoBasePath, 0755, true) && !is_dir($this->demoBasePath)) {
                throw new RuntimeException('Cannot create demo directory: ' . $this->demoBasePath);
            }

            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->demoBasePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }

            if (basename($item->getPathname()) === '.gitkeep') {
                continue;
            }

            unlink($item->getPathname());
        }
    }

    private function writeDemoFile(string $relativePath, string $contents): void
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $absolutePath = $this->demoBasePath . '/' . $relativePath;
        $dir = dirname($absolutePath);

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Cannot create demo subdirectory: ' . $dir);
        }

        if (file_put_contents($absolutePath, $contents) === false) {
            throw new RuntimeException('Cannot write demo file: ' . $absolutePath);
        }
    }

    private function writeLastResetTimestamp(int $timestamp): void
    {
        $payload = json_encode(['reset_at' => $timestamp], JSON_THROW_ON_ERROR);
        $this->writeDemoFile(self::LAST_RESET_FILE, $payload);
    }

    private function readLastResetTimestamp(): ?int
    {
        $path = $this->demoBasePath . '/' . self::LAST_RESET_FILE;
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        try {
            /** @var array{reset_at?: int|float|string} $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return isset($data['reset_at']) ? (int) $data['reset_at'] : null;
    }
}
