<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Themes;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Themes\Services\ThemeRuntimeService;
use PaginiumCMS\Http\Themes\Services\ThemeScriptIntegrityService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Stream;

/**
 * Serves declared theme assets/*.js for the active theme only (It.87m).
 */
final class ThemeAssetController
{
    public function __construct(
        private string $themesRoot,
        private SettingsRepositoryInterface $settings,
        private ThemeRuntimeService $runtime,
        private ThemeScriptIntegrityService $integrity,
    ) {
        $this->themesRoot = rtrim($themesRoot, '/');
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function serve(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $appearance = $this->settings->group('appearance');
        if (!(bool) ($appearance['themeScriptsEnabled'] ?? false)) {
            return $response->withStatus(404);
        }

        $themeId = trim((string) ($args['themeId'] ?? ''));
        $relative = ltrim(str_replace('\\', '/', (string) ($args['path'] ?? '')), '/');
        if ($themeId === '' || $relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return $response->withStatus(404);
        }

        if ($themeId !== $this->runtime->resolveActiveThemeId()) {
            return $response->withStatus(404);
        }

        if (!str_starts_with($relative, 'assets/')) {
            $relative = 'assets/' . $relative;
        }
        if (preg_match(ThemeScriptIntegrityService::PATH_PATTERN, $relative) !== 1) {
            return $response->withStatus(404);
        }

        $allowed = $this->integrity->declaredScriptPaths(
            $this->readManifest($this->themesRoot . '/' . $themeId)
        );
        if (!in_array($relative, $allowed, true)) {
            return $response->withStatus(404);
        }

        $base = realpath($this->themesRoot . '/' . $themeId);
        if ($base === false) {
            return $response->withStatus(404);
        }
        $candidate = $base . '/' . $relative;
        $real = realpath($candidate);
        if ($real === false || !is_file($real) || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
            return $response->withStatus(404);
        }

        $stream = fopen($real, 'rb');
        if ($stream === false) {
            return $response->withStatus(404);
        }

        $size = filesize($real);

        return $response
            ->withHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'public, max-age=3600')
            ->withHeader('Content-Length', (string) ($size !== false ? $size : 0))
            ->withBody(new Stream($stream));
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $themeRoot): array
    {
        $path = rtrim($themeRoot, '/') . '/theme.json';
        if (!is_file($path)) {
            return [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return [];
        }
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (\JsonException) {
            return [];
        }
    }
}
