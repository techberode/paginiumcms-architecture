<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Support\TestArtifactNaming;

/**
 * Vyčistí artefakty integračných testov z backend/storage.
 * Volá sa na konci `run-all-tests.zsh` (krok 12).
 *
 * Zachováva: SMTP a ostatné nastavenia (`settings.json`), navigáciu, reálne komentáre,
 * produkčné médiá, zálohy, logy, dev tokeny, reálne kontaktné správy.
 * Nemaže: produkčné účty mimo @example.com (testovacia konvencia).
 */
final class TestStorageCleaner
{
    public static function storageRoot(): string
    {
        return dirname(__DIR__, 2) . '/storage';
    }

    public static function contentRoot(): string
    {
        return self::storageRoot() . '/app/content';
    }

    public static function backendRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function purgeAll(): void
    {
        self::purgeCache();
        self::purgeOtpChallenges();
        self::purgeTestTrash();
        self::purgeLoginAttempts();
        self::purgeFirewall();
        self::purgeTestUsers();
        self::purgeTestMessages();
        self::purgeTestComments();
        self::purgeTestContentPages();
        self::pruneTestEntriesFromContentIndex();
        self::purgeTestMedia();
        self::purgeTestDrafts();
        self::purgeTestVersions();
        self::purgeTestBackups();
        self::purgeTestSettingsFile();
        self::purgeCodeEditorBackups();
        self::purgeCodeEditorTestModules();
    }

    public static function purgeCache(): void
    {
        self::deleteFilesInDir(self::storageRoot() . '/cache');
    }

    public static function purgeOtpChallenges(): void
    {
        $path = self::contentRoot() . '/data/otp-challenges.json';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function purgeTestTrash(): void
    {
        $dir = self::contentRoot() . '/trash';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.meta.json') ?: [] as $metaPath) {
            $raw = @file_get_contents($metaPath);
            if ($raw === false) {
                continue;
            }

            $meta = json_decode($raw, true);
            if (!is_array($meta)) {
                continue;
            }

            $originalPath = (string) ($meta['originalPath'] ?? '');
            $slug = basename($originalPath);
            $slug = preg_replace('/\.(md|json)$/i', '', $slug) ?? $slug;
            if (!TestArtifactNaming::isTestContentSlug($slug)) {
                continue;
            }

            $trashFilename = (string) ($meta['trashFilename'] ?? '');
            if ($trashFilename !== '') {
                $trashFile = $dir . '/' . $trashFilename;
                if (is_file($trashFile)) {
                    @unlink($trashFile);
                }
            }

            @unlink($metaPath);
        }
    }

    public static function purgeLoginAttempts(): void
    {
        $path = self::contentRoot() . '/data/security/login_attempts.json';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function purgeFirewall(): void
    {
        self::deleteFilesInDir(self::contentRoot() . '/data/security/firewall');
    }

    public static function purgeTestUsers(): void
    {
        $dir = self::contentRoot() . '/data/users';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/user_*.json') ?: [] as $file) {
            if (str_contains(basename($file), '.backup.')) {
                continue;
            }

            $raw = @file_get_contents($file);
            if ($raw === false || !self::isTestUserPayload($raw)) {
                continue;
            }

            @unlink($file);
            self::deleteUserBackups($file);
        }

        foreach (glob($dir . '/user_*.json.backup.*') ?: [] as $backup) {
            $raw = @file_get_contents($backup);
            if ($raw !== false && self::isTestUserPayload($raw)) {
                @unlink($backup);
                continue;
            }

            $main = preg_replace('/\.backup\.[^.]+$/', '', $backup);
            if ($main !== null && $main !== $backup && !is_file($main)) {
                @unlink($backup);
            }
        }

        self::rebuildUserIndex();
    }

    /**
     * Wipes all user flat-files — only for PHPUnit setup/fresh-install tests.
     * Unlike purgeTestUsers(), removes production emails too when APP_ENV=testing.
     */
    public static function purgeAllUsersForTesting(): void
    {
        if (($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: '') !== 'testing') {
            return;
        }

        $dir = self::contentRoot() . '/data/users';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/user_*.json') ?: [] as $file) {
            if (str_contains(basename($file), '.backup.')) {
                continue;
            }

            @unlink($file);
            self::deleteUserBackups($file);
        }

        foreach (glob($dir . '/user_*.json.backup.*') ?: [] as $backup) {
            @unlink($backup);
        }

        self::rebuildUserIndex();
    }

    public static function purgeTestMessages(): void
    {
        $dir = self::contentRoot() . '/data/messages';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false || !self::isTestMessagePayload($raw)) {
                continue;
            }

            @unlink($file);
        }
    }

    public static function purgeTestComments(): void
    {
        $path = self::contentRoot() . '/data/comments.json';
        if (!is_file($path)) {
            return;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return;
        }

        $filtered = array_values(array_filter(
            $data,
            static function (mixed $entry): bool {
                if (!is_array($entry)) {
                    return true;
                }

                $email = $entry['email'] ?? $entry['authorEmail'] ?? '';
                if (!is_string($email)) {
                    return true;
                }

                return !str_ends_with(strtolower($email), '@example.com');
            }
        ));

        @file_put_contents(
            $path,
            json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
        );
    }

    public static function purgeTestContentPages(): void
    {
        foreach (['pages', 'blog'] as $subdir) {
            self::purgeTestContentInDir(self::contentRoot() . '/' . $subdir);
        }
    }

    public static function purgeTestDrafts(): void
    {
        $dir = self::contentRoot() . '/data/drafts';
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $slug = TestArtifactNaming::slugFromBasename($file->getFilename());
            if (TestArtifactNaming::isTestContentSlug($slug)) {
                @unlink($file->getPathname());
            }
        }
    }

    public static function purgeTestVersions(): void
    {
        $dir = self::contentRoot() . '/data/versions';
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $data = json_decode($raw, true);
            $contentId = is_array($data) ? (string) ($data['contentId'] ?? '') : '';
            if ($contentId !== '' && TestArtifactNaming::isTestContentReference($contentId)) {
                @unlink($file);
            }
        }
    }

    public static function purgeTestBackups(): void
    {
        $dir = self::storageRoot() . '/backups';
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            if (!str_ends_with(strtolower($name), '.zip')) {
                continue;
            }

            $stem = preg_replace('/\.zip$/i', '', $name) ?? $name;
            if (self::isTestBackupStem($stem)) {
                @unlink($file->getPathname());
            }
        }
    }

    private static function purgeTestContentInDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            if (str_contains($file->getFilename(), '.backup.')) {
                continue;
            }

            $slug = TestArtifactNaming::slugFromBasename($file->getFilename());
            if (TestArtifactNaming::isTestContentSlug($slug)) {
                @unlink($file->getPathname());
                self::deleteMatchingBackups($file->getPathname());
            }
        }
    }

    public static function pruneTestEntriesFromContentIndex(): void
    {
        $path = self::contentRoot() . '/data/index/content.json';
        if (!is_file($path)) {
            return;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return;
        }

        $wrapped = array_key_exists('items', $decoded) && is_array($decoded['items']);
        $items = $wrapped ? $decoded['items'] : $decoded;

        $filtered = array_values(array_filter(
            $items,
            static function (mixed $row): bool {
                if (!is_array($row)) {
                    return false;
                }

                $slug = (string) ($row['slug'] ?? '');

                return !TestArtifactNaming::isTestContentSlug($slug);
            }
        ));

        $payload = $wrapped
            ? array_merge($decoded, ['items' => $filtered])
            : ['version' => 1, 'items' => $filtered];

        @file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    public static function purgeTestMedia(): void
    {
        $mediaRoot = self::contentRoot() . '/media';
        if (!is_dir($mediaRoot)) {
            return;
        }

        $registryPath = $mediaRoot . '/registry.json';
        if (is_file($registryPath)) {
            $raw = @file_get_contents($registryPath);
            $registry = is_string($raw) ? json_decode($raw, true) : null;
            if (is_array($registry)) {
                $kept = [];
                foreach ($registry as $entry) {
                    if (!is_array($entry)) {
                        $kept[] = $entry;
                        continue;
                    }

                    $fileName = (string) ($entry['fileName'] ?? basename((string) ($entry['path'] ?? '')));
                    if (TestArtifactNaming::isTestMediaFileName($fileName)) {
                        $relativePath = (string) ($entry['path'] ?? '');
                        if ($relativePath !== '') {
                            $absolute = self::contentRoot() . '/' . ltrim($relativePath, '/');
                            if (is_file($absolute)) {
                                @unlink($absolute);
                            }
                        }
                        continue;
                    }

                    $kept[] = $entry;
                }

                @file_put_contents(
                    $registryPath,
                    json_encode($kept, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
                );
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mediaRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            if ($name === '.gitkeep' || $name === 'registry.json' || $name === 'folders.json') {
                continue;
            }

            if (TestArtifactNaming::isTestMediaFileName($name)) {
                @unlink($file->getPathname());
            }
        }
    }

    public static function purgeTestSettingsFile(): void
    {
        $path = self::contentRoot() . '/data/settings.testing.json';
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function purgeCodeEditorBackups(): void
    {
        self::deleteFilesInDir(self::storageRoot() . '/backups/code');
    }

    public static function purgeCodeEditorTestModules(): void
    {
        foreach (glob(self::backendRoot() . '/app/Modules/CodeEditorFlowTest_*.php') ?: [] as $file) {
            @unlink($file);
        }
    }

    /**
     * Spočíta iba testovacie artefakty (generické názvy / @example.com) — nie produkčné dáta.
     *
     * @return array{
     *   test_users: int,
     *   test_user_backups: int,
     *   trash_files: int,
     *   test_comments: int,
     *   media_files: int,
     *   test_pages: int,
     *   contact_messages: int,
     *   test_page_backups: int,
     *   test_drafts: int,
     *   test_versions: int,
     *   test_backups: int
     * }
     */
    public static function scanTestArtifacts(): array
    {
        return [
            'test_users' => self::countTestUsers(),
            'test_user_backups' => self::countTestUserBackups(),
            'trash_files' => self::countTestTrashItems(),
            'test_comments' => self::countTestComments(),
            'media_files' => self::countTestMediaFiles(),
            'test_pages' => self::countTestPages(),
            'contact_messages' => self::countTestMessages(),
            'test_page_backups' => self::countTestPageBackups(),
            'test_drafts' => self::countTestDrafts(),
            'test_versions' => self::countTestVersions(),
            'test_backups' => self::countTestBackupArchives(),
        ];
    }

    /**
     * @return array{before: array<string, int>, after: array<string, int>}
     */
    public static function purgeWithReport(): array
    {
        $before = self::scanTestArtifacts();
        self::purgeAll();
        $after = self::scanTestArtifacts();

        return [
            'before' => $before,
            'after' => $after,
        ];
    }

    /**
     * Ľudsky čitateľný report (generické názvy, bez reálnych e-mailov).
     *
     * @param array{before: array<string, int>, after: array<string, int>} $report
     */
    public static function formatPurgeReport(array $report): string
    {
        $before = $report['before'];
        $lines = [
            'Nájdené testovacie artefakty (pred cleanup):',
            sprintf('  • test_users (*@example.com) ............ %d', $before['test_users']),
            sprintf('  • user_backups (test / orphan) ......... %d', $before['test_user_backups']),
            sprintf('  • trash/* ............................. %d', $before['trash_files']),
            sprintf('  • comments (@example.com v registry) .. %d', $before['test_comments']),
            sprintf('  • media/uploads (test uploady) ........ %d', $before['media_files']),
            sprintf('  • test pages (seo-test-*, bulk-*, …) .. %d', $before['test_pages']),
            sprintf('  • page backups (test slug) ............ %d', $before['test_page_backups']),
            sprintf('  • contact messages (data/messages) .... %d', $before['contact_messages']),
            sprintf('  • test drafts ......................... %d', $before['test_drafts']),
            sprintf('  • test versions ....................... %d', $before['test_versions']),
            sprintf('  • test backup zips .................... %d', $before['test_backups']),
            '',
            'Po cleanup (zvyšok test artefaktov):',
            sprintf('  • test_users .......................... %d', $report['after']['test_users']),
            sprintf('  • trash + media + test pages .......... %d / %d / %d',
                $report['after']['trash_files'],
                $report['after']['media_files'],
                $report['after']['test_pages']
            ),
            '',
            '✅ Reálne účty, SMTP/nastavenia (`settings.json`), navigácia, produkčné stránky, médiá, zálohy a logy neboli cielené.',
        ];

        return implode("\n", $lines);
    }

    private static function countTestUsers(): int
    {
        $dir = self::contentRoot() . '/data/users';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/user_*.json') ?: [] as $file) {
            if (str_contains(basename($file), '.backup.')) {
                continue;
            }
            $raw = @file_get_contents($file);
            if ($raw !== false && self::isTestUserPayload($raw)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestUserBackups(): int
    {
        $dir = self::contentRoot() . '/data/users';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/user_*.json.backup.*') ?: [] as $backup) {
            $main = preg_replace('/\.backup\.[^.]+$/', '', $backup);
            if ($main !== null && is_file($main)) {
                $raw = @file_get_contents($main);
                if ($raw !== false && self::isTestUserPayload($raw)) {
                    ++$count;
                    continue;
                }
            }

            $backupRaw = @file_get_contents($backup);
            if ($backupRaw !== false && self::isTestUserPayload($backupRaw)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestComments(): int
    {
        $path = self::contentRoot() . '/data/comments.json';
        if (!is_file($path)) {
            return 0;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return 0;
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return 0;
        }

        $count = 0;
        foreach ($data as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $email = $entry['email'] ?? $entry['authorEmail'] ?? '';
            if (is_string($email) && str_ends_with(strtolower($email), '@example.com')) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestMediaFiles(): int
    {
        $mediaRoot = self::contentRoot() . '/media';
        if (!is_dir($mediaRoot)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mediaRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }
            $name = $file->getFilename();
            if ($name === '.gitkeep' || $name === 'registry.json' || $name === 'folders.json') {
                continue;
            }
            if (TestArtifactNaming::isTestMediaFileName($name)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestTrashItems(): int
    {
        $dir = self::contentRoot() . '/trash';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.meta.json') ?: [] as $metaPath) {
            $raw = @file_get_contents($metaPath);
            if ($raw === false) {
                continue;
            }

            $meta = json_decode($raw, true);
            if (!is_array($meta)) {
                continue;
            }

            $originalPath = (string) ($meta['originalPath'] ?? '');
            $slug = basename($originalPath);
            $slug = preg_replace('/\.(md|json)$/i', '', $slug) ?? $slug;
            if (TestArtifactNaming::isTestContentSlug($slug)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestMessages(): int
    {
        $dir = self::contentRoot() . '/data/messages';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if ($raw !== false && self::isTestMessagePayload($raw)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestPages(): int
    {
        $count = 0;
        foreach (['pages', 'blog'] as $subdir) {
            $dir = self::contentRoot() . '/' . $subdir;
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                    continue;
                }

                if (str_contains($file->getFilename(), '.backup.')) {
                    continue;
                }

                $slug = TestArtifactNaming::slugFromBasename($file->getFilename());
                if (TestArtifactNaming::isTestContentSlug($slug)) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    private static function countTestPageBackups(): int
    {
        $count = 0;
        foreach (['pages', 'blog'] as $subdir) {
            $dir = self::contentRoot() . '/' . $subdir;
            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                    continue;
                }

                if (!str_contains($file->getFilename(), '.backup.')) {
                    continue;
                }

                $base = preg_replace('/\.backup\.[^.]+$/', '', $file->getFilename()) ?? '';
                $slug = TestArtifactNaming::slugFromBasename($base);
                if (TestArtifactNaming::isTestContentSlug($slug)) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    private static function countTestDrafts(): int
    {
        $dir = self::contentRoot() . '/data/drafts';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $slug = TestArtifactNaming::slugFromBasename($file->getFilename());
            if (TestArtifactNaming::isTestContentSlug($slug)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestVersions(): int
    {
        $dir = self::contentRoot() . '/data/versions';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $data = json_decode($raw, true);
            $contentId = is_array($data) ? (string) ($data['contentId'] ?? '') : '';
            if ($contentId !== '' && TestArtifactNaming::isTestContentReference($contentId)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function countTestBackupArchives(): int
    {
        $dir = self::storageRoot() . '/backups';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            if (!str_ends_with(strtolower($name), '.zip')) {
                continue;
            }

            $stem = preg_replace('/\.zip$/i', '', $name) ?? $name;
            if (self::isTestBackupStem($stem)) {
                ++$count;
            }
        }

        return $count;
    }

    private static function isTestBackupStem(string $stem): bool
    {
        if (TestArtifactNaming::isTestContentSlug($stem)) {
            return true;
        }

        return preg_match('/[_-](qa-|seo-test-|bulk-[ab]-|trash-test-|purge-trash-|bulk-trash-)/', $stem) === 1;
    }

    private static function isTestUserPayload(string $raw): bool
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return true;
        }

        $email = $data['email'] ?? null;
        if (!is_string($email) || $email === '') {
            return true;
        }

        return str_ends_with(strtolower($email), '@example.com');
    }

    private static function isTestMessagePayload(string $raw): bool
    {
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return false;
        }

        $email = $data['email'] ?? null;
        if (!is_string($email) || $email === '') {
            return false;
        }

        return str_ends_with(strtolower($email), '@example.com');
    }

    private static function deleteUserBackups(string $userJsonPath): void
    {
        foreach (glob($userJsonPath . '.backup.*') ?: [] as $backup) {
            @unlink($backup);
        }
    }

    /**
     * Rebuilds `data/index/users.json` from remaining flat-files (prevents orphan index after purge).
     */
    private static function rebuildUserIndex(): void
    {
        $usersDir = self::contentRoot() . '/data/users';
        $indexPath = self::contentRoot() . '/data/index/users.json';
        $byId = [];
        $byEmail = [];
        $byUsername = [];

        foreach (glob($usersDir . '/user_*.json') ?: [] as $file) {
            if (str_contains(basename($file), '.backup.')) {
                continue;
            }

            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }

            $data = json_decode($raw, true);
            if (
                !is_array($data)
                || !isset($data['id'], $data['email'])
                || !is_string($data['id'])
                || !is_string($data['email'])
                || $data['id'] === ''
                || trim($data['email']) === ''
            ) {
                continue;
            }

            $id = $data['id'];
            $email = strtolower(trim($data['email']));
            $username = strtolower(trim((string) ($data['username'] ?? '')));
            $byId[$id] = [
                'id' => $id,
                'email' => $email,
                'username' => $username,
                'resetTokenHash' => isset($data['resetTokenHash']) ? (string) $data['resetTokenHash'] : null,
                'resetTokenExpires' => isset($data['resetTokenExpires']) ? (int) $data['resetTokenExpires'] : null,
            ];
            $byEmail[$email] = $id;
            if ($username !== '') {
                $byUsername[$username] = $id;
            }
        }

        $indexDir = dirname($indexPath);
        if (!is_dir($indexDir)) {
            mkdir($indexDir, 0755, true);
        }

        file_put_contents(
            $indexPath,
            json_encode([
                'version' => 1,
                'updated_at' => date('c'),
                'by_id' => $byId,
                'by_email' => $byEmail,
                'by_username' => $byUsername,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private static function deleteMatchingBackups(string $filePath): void
    {
        foreach (glob($filePath . '.backup.*') ?: [] as $backup) {
            @unlink($backup);
        }
    }

    private static function deleteFilesInDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}
