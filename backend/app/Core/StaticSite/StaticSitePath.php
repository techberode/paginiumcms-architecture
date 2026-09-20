<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\StaticSite;

use InvalidArgumentException;

/**
 * Allow-listed relative paths under storage/app/static/ (It.48). Never .php.
 */
final class StaticSitePath
{
    public const ROOT = 'static';
    public const MANIFEST = 'static/manifest.json';
    public const PUBLIC_PREFIX = '/static-html';
    public const HOME_SLUG = 'home';

    /**
     * First-segment SPA / system slugs. Compiled pages with these names stay 404
     * on the public HTML surface so /login and /dashboard cannot be stolen.
     *
     * @var list<string>
     */
    public const RESERVED_SLUGS = [
        'account', 'admin', 'analytics', 'api', 'api-keys', 'articles', 'assets',
        'audit', 'backups', 'blog', 'blueprints', 'categories', 'code-editor',
        'comments', 'cookies', 'dashboard', 'debug', 'demo', 'developer',
        'docs', 'editorial-calendar', 'events', 'extensions', 'features',
        'feed', 'firewall', 'forgot-password', 'gallery', 'github', 'health',
        'help', 'kanban', 'login', 'logout', 'logs', 'mail', 'maintenance',
        'media', 'messages', 'navigation', 'newsletter', 'notifications',
        'origin', 'pages', 'platform', 'preview', 'privacy', 'project-planner',
        'register', 'reset-password', 'redirects', 'robots', 'roles',
        'scheduler', 'security', 'security-audit', 'settings', 'setup',
        'shortcodes', 'sitemap', 'snippets', 'static', 'static-html',
        'storage', 'team-chat', 'teams', 'themes', 'time-tracker', 'trash',
        'translations', 'update', 'users', 'verify', 'webhooks', 'well-known',
        'widgets',
    ];

    public static function assertType(string $type): string
    {
        $normalized = strtolower(trim($type));
        if ($normalized !== 'page' && $normalized !== 'article') {
            throw new InvalidArgumentException('Static compile type must be page or article.');
        }

        return $normalized;
    }

    public static function assertSlug(string $slug): string
    {
        $normalized = strtolower(trim($slug));
        if ($normalized === '' || strlen($normalized) > 180) {
            throw new InvalidArgumentException('Static compile slug is empty or too long.');
        }
        if (str_contains($normalized, '..') || str_contains($normalized, '/') || str_contains($normalized, '\\')) {
            throw new InvalidArgumentException('Static compile slug must not contain path separators.');
        }
        if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $normalized) !== 1) {
            throw new InvalidArgumentException('Static compile slug must be lowercase alphanumeric with hyphens.');
        }

        return $normalized;
    }

    public static function isReservedSlug(string $slug): bool
    {
        try {
            $normalized = self::assertSlug($slug);
        } catch (InvalidArgumentException) {
            return true;
        }

        return in_array($normalized, self::RESERVED_SLUGS, true);
    }

    public static function directoryForType(string $type): string
    {
        return self::assertType($type) === 'article' ? 'blog' : 'pages';
    }

    public static function htmlRelative(string $type, string $slug): string
    {
        $dir = self::directoryForType($type);
        $safeSlug = self::assertSlug($slug);

        return self::ROOT . '/' . $dir . '/' . $safeSlug . '/index.html';
    }
}
