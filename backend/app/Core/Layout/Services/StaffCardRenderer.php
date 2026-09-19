<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Layout\Services;

/**
 * Placeholder island for [staff-card] / [staff-team] (It.93o-3).
 * Public SPA hydrates published cards from GET /api/public/staff.
 */
final class StaffCardRenderer
{
    /**
     * @param array<string, string> $attrs
     */
    public static function render(string $name, array $attrs): string
    {
        $mode = $name === 'staff-card' ? 'user' : ((trim($attrs['id'] ?? '') !== '') ? 'team' : 'type');
        $user = self::attr($attrs['user'] ?? '');
        $type = self::attr($attrs['type'] ?? '');
        $team = self::attr($attrs['id'] ?? '');

        return '<section class="pg-staff-cards"'
            . ' data-staff-mode="' . $mode . '"'
            . ' data-staff-user="' . $user . '"'
            . ' data-staff-type="' . $type . '"'
            . ' data-staff-team="' . $team . '"'
            . '></section>';
    }

    private static function attr(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
