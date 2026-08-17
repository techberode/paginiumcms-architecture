<?php

declare(strict_types=1);

namespace PaginiumCMS\Support;

use PaginiumCMS\Tests\Support\TestStorageCleaner;
use RuntimeException;

/**
 * Maintainer dev-machine storage hygiene — prefix-only purge via {@see TestStorageCleaner}.
 */
final class DevStorageHygiene
{
    public static function contentRoot(): string
    {
        return TestStorageCleaner::contentRoot();
    }

    public static function storageRoot(): string
    {
        return TestStorageCleaner::storageRoot();
    }

    public static function backendRoot(): string
    {
        return TestStorageCleaner::backendRoot();
    }

    public static function assertAllowedEnvironment(bool $force = false): void
    {
        if ($force) {
            return;
        }

        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
        if ($env === 'production') {
            throw new RuntimeException('dev:hygiene is blocked when APP_ENV=production (use --force only if you are certain).');
        }
    }

    /**
     * @return array<string, int>
     */
    public static function scan(): array
    {
        $counts = TestStorageCleaner::scanTestArtifacts();
        ksort($counts);

        return $counts;
    }

    /**
     * @return array{before: array<string, int>, after: array<string, int>, rebuilt_index: bool}
     */
    public static function purge(bool $includeLogs = false, bool $rebuildIndex = true): array
    {
        $before = self::scan();
        TestStorageCleaner::purgeAll();

        if ($includeLogs) {
            self::deleteTree(self::storageRoot() . '/logs');
        }

        self::deleteTree(self::backendRoot() . '/data/metrics');

        $rebuilt = false;
        if ($rebuildIndex) {
            $rebuilt = self::rebuildContentIndex();
        }

        return [
            'before' => $before,
            'after' => self::scan(),
            'rebuilt_index' => $rebuilt,
        ];
    }

    /**
     * @param array{before: array<string, int>, after: array<string, int>, rebuilt_index: bool} $report
     */
    public static function formatReport(array $report): string
    {
        $lines = [
            'Dev storage hygiene (prefix / @example.com only):',
            '',
            'Before:',
        ];

        foreach ($report['before'] as $key => $value) {
            $lines[] = sprintf('  • %-28s %d', str_replace('_', ' ', $key) . ' ..', $value);
        }

        $lines[] = '';
        $lines[] = 'After:';
        foreach ($report['after'] as $key => $value) {
            $lines[] = sprintf('  • %-28s %d', str_replace('_', ' ', $key) . ' ..', $value);
        }

        $lines[] = '';
        $lines[] = 'Preserved: real pages/articles, production media, real users, settings, backups, non-test trash.';
        $lines[] = 'Test slugs must use prefix `' . TestArtifactNaming::QA_PREFIX . '` or known PHPUnit patterns (see TestArtifactNaming).';

        if ($report['rebuilt_index']) {
            $lines[] = 'Content index rebuilt via content:diagnose --fix.';
        }

        return implode("\n", $lines);
    }

    private static function rebuildContentIndex(): bool
    {
        $php = escapeshellarg(PHP_BINARY);
        $console = escapeshellarg(self::backendRoot() . '/bin/console');
        $command = "{$php} {$console} content:diagnose --fix";

        exec($command, $output, $exitCode);

        return $exitCode === 0;
    }

    private static function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isFile()) {
                if ($item->getFilename() === '.gitkeep') {
                    continue;
                }

                @unlink($item->getPathname());
                continue;
            }

            @rmdir($item->getPathname());
        }
    }
}
