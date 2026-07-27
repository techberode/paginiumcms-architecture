<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Scheduler\Services;

/**
 * Jobs that must not be manageable or runnable by regular ADMIN (audit A3-JOBDEPLOY).
 */
final class PrivilegedJobPolicy
{
    /** @var list<string> */
    public const SUPER_ADMIN_HANDLERS = [
        'system.deploy',
    ];

    /**
     * @param array<string, mixed>|null $job
     */
    public static function requiresSuperAdmin(?array $job): bool
    {
        if ($job === null) {
            return false;
        }

        $handler = (string) ($job['handler'] ?? '');

        return in_array($handler, self::SUPER_ADMIN_HANDLERS, true);
    }

    public static function skipInScheduledRunDue(string $handlerKey): bool
    {
        return $handlerKey === 'system.deploy';
    }
}
