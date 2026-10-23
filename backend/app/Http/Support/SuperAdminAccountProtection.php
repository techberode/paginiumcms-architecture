<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Support;

use PaginiumCMS\Modules\Security\Models\User;

/**
 * Prevents privilege escalation where ADMIN mutates an existing SUPER_ADMIN account
 * (email/password/deactivate/delete) without being SUPER_ADMIN.
 */
final class SuperAdminAccountProtection
{
    public static function denyMutatingSuperAdmin(?User $actor, User $target): ?string
    {
        if (!$target->isSuperAdmin()) {
            return null;
        }

        if ($actor !== null && $actor->isSuperAdmin()) {
            return null;
        }

        return 'Účet super administrátora môže meniť len super administrátor.';
    }

    public static function denyUnlessSuperAdminActor(?User $actor): ?string
    {
        if ($actor === null) {
            return 'Neprihlásený používateľ';
        }

        if (!$actor->isSuperAdmin()) {
            return 'Operáciu môže vykonať len super administrátor.';
        }

        return null;
    }
}
