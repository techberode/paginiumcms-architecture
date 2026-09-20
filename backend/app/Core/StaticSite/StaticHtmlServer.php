<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\StaticSite;

use InvalidArgumentException;
use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use Throwable;

/**
 * Allow-listed public reader for compiled index.html (It.48b).
 * Never serves .php, manifest.json, or anything outside storage/app/static/.
 */
final class StaticHtmlServer
{
    /**
     * Compiled pages are snapshots (sanitized body, escaped title). No script-src.
     */
    public const CONTENT_SECURITY_POLICY = "default-src 'none'; img-src 'self' https: data:; style-src 'self' 'unsafe-inline'; font-src 'self' https: data:; base-uri 'none'; form-action 'none'; frame-ancestors 'none'; object-src 'none'";

    public function __construct(
        private FileReaderInterface $reader,
        private StaticSiteSettings $settings,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->settings->servesPublicHtml();
    }

    /**
     * @return array{html: string, path: string}|null
     */
    public function load(string $type, string $slug): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $type = StaticSitePath::assertType($type);
            $slug = StaticSitePath::assertSlug($slug);
        } catch (InvalidArgumentException) {
            return null;
        }

        if ($type === 'page' && StaticSitePath::isReservedSlug($slug)) {
            return null;
        }

        $relative = StaticSitePath::htmlRelative($type, $slug);
        if (!$this->isSafeHtmlRelative($relative) || !$this->reader->exists($relative)) {
            return null;
        }

        if (!$this->realpathIsInsideStaticTree($relative)) {
            return null;
        }

        try {
            $html = $this->reader->read($relative);
        } catch (Throwable) {
            return null;
        }

        return [
            'html' => $html,
            'path' => $relative,
        ];
    }

    private function isSafeHtmlRelative(string $relative): bool
    {
        if ($relative === '' || str_contains($relative, '..') || str_contains($relative, "\0")) {
            return false;
        }
        if (str_contains(strtolower($relative), '.php')) {
            return false;
        }
        if (!str_starts_with($relative, StaticSitePath::ROOT . '/')) {
            return false;
        }

        return str_ends_with($relative, '/index.html');
    }

    private function realpathIsInsideStaticTree(string $relative): bool
    {
        $base = realpath($this->reader->getBasePath());
        if ($base === false) {
            // vfs / ephemeral trees: FileReader already confined the relative path.
            return true;
        }

        $staticRoot = realpath($base . DIRECTORY_SEPARATOR . StaticSitePath::ROOT);
        if ($staticRoot === false || !is_dir($staticRoot)) {
            return false;
        }

        $candidate = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $real = realpath($candidate);
        if ($real === false || !is_file($real)) {
            return false;
        }
        if (basename($real) !== 'index.html') {
            return false;
        }
        if (strtolower((string) pathinfo($real, PATHINFO_EXTENSION)) !== 'html') {
            return false;
        }

        return str_starts_with($real, $staticRoot . DIRECTORY_SEPARATOR);
    }
}
