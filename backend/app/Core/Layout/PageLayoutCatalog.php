<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout;

/**
 * Allow-listed page layout structure templates (It.58c).
 * Distinct from chrome/page-type templates (home, contact, …).
 */
final class PageLayoutCatalog
{
    public const DEFAULT_TEMPLATE = 'hero-content';

    public const DEFAULT_BUILDER_MODE = 'templates';

    /** @var list<string> */
    public const TEMPLATES = [
        'single',
        'hero-content',
        'two-column',
        'landing',
        'blog-article',
    ];

    /** @var list<string> */
    public const BUILDER_MODES = [
        'templates',
        'shortcodes',
        'outline',
        'developer',
    ];

    public static function isValidTemplate(string $id): bool
    {
        return in_array($id, self::TEMPLATES, true);
    }

    public static function isValidBuilderMode(string $mode): bool
    {
        return in_array($mode, self::BUILDER_MODES, true);
    }

    /**
     * Normalize unknown / empty template IDs to the catalog default.
     */
    public static function normalizeTemplate(?string $id): string
    {
        $trimmed = trim((string) $id);
        if ($trimmed === '' || !self::isValidTemplate($trimmed)) {
            return self::DEFAULT_TEMPLATE;
        }

        return $trimmed;
    }

    /**
     * Normalize unknown / empty builder modes to the catalog default.
     */
    public static function normalizeBuilderMode(?string $mode): string
    {
        $trimmed = trim((string) $mode);
        if ($trimmed === '' || !self::isValidBuilderMode($trimmed)) {
            return self::DEFAULT_BUILDER_MODE;
        }

        return $trimmed;
    }
}
