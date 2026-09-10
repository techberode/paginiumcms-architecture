<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Themes\Services;

use PaginiumCMS\Core\Developer\DeveloperModeGate;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Http\Themes\Exceptions\ThemeStudioException;
use PaginiumCMS\Http\Themes\Models\ThemeRecord;
use PaginiumCMS\Support\JsonHelper;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

/**
 * Persist Theme Studio buffers to the theme package tree (It.88g).
 *
 * Validates with the same untrusted policy as ZIP import before any write.
 *
 * @phpstan-type StudioMarker array{line: int, message: string}
 * @phpstan-type PersistIssue array{relativePath: string, valid: bool, markers: list<StudioMarker>}
 * @phpstan-type PersistResult array{
 *   blocked: bool,
 *   themeId: string,
 *   written: list<string>,
 *   frontendCssCopied: bool,
 *   issues: list<PersistIssue>
 * }
 */
final class ThemeStudioPersistService
{
    public const MAX_FILES = 24;

    public const MAX_TOTAL_BYTES = 1572864;

    public function __construct(
        private ThemeStudioService $studio,
        private ThemeStudioValidator $validator,
        private ThemeManifestValidator $manifests,
        private ThemeScriptIntegrityService $integrity,
        private ThemeRegistry $registry,
        private string $frontendThemesRoot,
        private ?DeveloperModeGate $developerGate = null,
        private ?LoggerInterface $logger = null,
    ) {
        $this->frontendThemesRoot = rtrim($frontendThemesRoot, '/\\');
    }

    /**
     * @param array<mixed> $files
     * @return PersistResult
     */
    public function save(string $themeId, array $files): array
    {
        $id = trim($themeId);
        $this->studio->assertSafeThemeId($id);
        $buffers = $this->normalizeFiles($files);
        if (!isset($buffers['theme.json'])) {
            throw new ThemeStudioException('theme.json is required to save.', 400);
        }

        $this->assertManifestId($id, $buffers['theme.json']);
        $issues = $this->validateAll($id, $buffers);
        $this->assertDeclaredJavascript($buffers);

        if ($issues !== []) {
            $this->logger?->warning('Theme studio save blocked', LogSanitizer::context([
                'themeId' => $id,
                'issues' => (string) count($issues),
            ]));

            return [
                'blocked' => true,
                'themeId' => $id,
                'written' => [],
                'frontendCssCopied' => false,
                'issues' => $issues,
            ];
        }

        $written = $this->studio->writeTextFiles($id, $buffers);
        $this->sealJavascript($id);
        $this->upsertRegistry($id);
        $frontendCssCopied = $this->copyFrontendCssIfAllowed($id, $buffers);

        $this->logger?->info('Theme studio saved', LogSanitizer::context([
            'themeId' => $id,
            'written' => (string) count($written),
            'frontendCss' => $frontendCssCopied ? '1' : '0',
        ]));

        return [
            'blocked' => false,
            'themeId' => $id,
            'written' => $written,
            'frontendCssCopied' => $frontendCssCopied,
            'issues' => [],
        ];
    }

    /**
     * @param array<mixed> $files
     * @return array<string, string>
     */
    private function normalizeFiles(array $files): array
    {
        if ($files === []) {
            throw new ThemeStudioException('files is required.', 400);
        }
        if (count($files) > self::MAX_FILES) {
            throw new ThemeStudioException('Too many theme files in the save payload.', 413);
        }

        $buffers = [];
        $total = 0;
        foreach ($files as $path => $content) {
            if (!is_string($path) || !is_string($content)) {
                throw new ThemeStudioException('Each theme file must be a string path and body.', 400);
            }
            $relative = $this->studio->assertBufferPath($path);
            if (strlen($content) > ThemeStudioService::MAX_FILE_BYTES) {
                throw new ThemeStudioException('Theme file is too large to open in the studio.', 413);
            }
            $total += strlen($content);
            if ($total > self::MAX_TOTAL_BYTES) {
                throw new ThemeStudioException('Theme save payload is too large.', 413);
            }
            $buffers[$relative] = $content;
        }

        return $buffers;
    }

    private function assertManifestId(string $themeId, string $json): void
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = JsonHelper::decode($json);
        } catch (\JsonException $exception) {
            throw new ThemeStudioException('theme.json is not valid JSON: ' . $exception->getMessage(), 400);
        }

        try {
            $manifestId = $this->manifests->validate($decoded, $themeId);
        } catch (RuntimeException $exception) {
            throw new ThemeStudioException($exception->getMessage(), 400);
        }

        if ($manifestId !== $themeId) {
            throw new ThemeStudioException('theme.json id must match the theme folder id.', 400);
        }
    }

    /**
     * @param array<string, string> $buffers
     * @return list<PersistIssue>
     */
    private function validateAll(string $themeId, array $buffers): array
    {
        $issues = [];
        foreach ($buffers as $path => $content) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($extension, ['html', 'css', 'js', 'json'], true)) {
                continue;
            }
            $result = $this->validator->validate($themeId, $path, $content);
            if ($result['valid']) {
                continue;
            }
            $issues[] = [
                'relativePath' => $result['relativePath'],
                'valid' => false,
                'markers' => $result['markers'],
            ];
        }

        return $issues;
    }

    /**
     * @param array<string, string> $buffers
     */
    private function assertDeclaredJavascript(array $buffers): void
    {
        /** @var array<string, mixed> $manifest */
        $manifest = JsonHelper::decode($buffers['theme.json']);
        $declared = $this->integrity->declaredScriptPaths($manifest);
        foreach ($buffers as $path => $_content) {
            if (!str_ends_with(strtolower($path), '.js')) {
                continue;
            }
            if (!in_array($path, $declared, true)) {
                throw new ThemeStudioException(
                    'Undeclared theme JavaScript is not allowed: ' . $path,
                    400
                );
            }
        }
    }

    private function sealJavascript(string $themeId): void
    {
        $dir = $this->studio->ensureThemeDirectory($themeId);
        $manifestPath = $dir . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($manifestPath)) {
            return;
        }

        try {
            /** @var array<string, mixed> $manifest */
            $manifest = JsonHelper::decode((string) file_get_contents($manifestPath));
        } catch (\JsonException) {
            return;
        }

        if ($this->integrity->declaredScriptPaths($manifest) === []) {
            return;
        }

        $this->integrity->sealManifest($dir, $manifest);
    }

    private function upsertRegistry(string $themeId): void
    {
        $existing = $this->registry->get($themeId);
        $this->registry->upsert(new ThemeRecord(
            $themeId,
            $existing !== null && $existing->enabled,
            $existing !== null && $existing->installedAt !== ''
                ? $existing->installedAt
                : gmdate('c'),
        ));
    }

    /**
     * @param array<string, string> $buffers
     */
    private function copyFrontendCssIfAllowed(string $themeId, array $buffers): bool
    {
        if ($this->developerGate === null || !$this->developerGate->isUnlocked()) {
            return false;
        }

        $frontendDir = $this->frontendThemesRoot . DIRECTORY_SEPARATOR . $themeId;
        $frontendReal = realpath($frontendDir);
        $rootReal = realpath($this->frontendThemesRoot);
        if ($frontendReal === false || $rootReal === false || !is_dir($frontendReal)) {
            return false;
        }
        if ($frontendReal !== $rootReal && !str_starts_with($frontendReal, $rootReal . DIRECTORY_SEPARATOR)) {
            return false;
        }

        $copied = false;
        foreach ($buffers as $path => $content) {
            if (!str_starts_with($path, 'assets/') || !str_ends_with(strtolower($path), '.css')) {
                continue;
            }
            $name = basename($path);
            if ($name === '' || str_contains($name, '..')) {
                continue;
            }
            $target = $frontendReal . DIRECTORY_SEPARATOR . $name;
            if (@file_put_contents($target, $content, LOCK_EX) === false) {
                continue;
            }
            $copied = true;
        }

        return $copied;
    }
}
