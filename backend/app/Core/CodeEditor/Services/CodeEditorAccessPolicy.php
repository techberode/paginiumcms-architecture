<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\CodeEditor\Services;

/**
 * Production code editor is off unless CODE_EDITOR_ENABLED=true (SUPER_ADMIN + 2FA still required).
 */
final class CodeEditorAccessPolicy
{
    public function isEnabled(): bool
    {
        $flag = getenv('CODE_EDITOR_ENABLED');
        if ($flag !== false && $flag !== '') {
            return filter_var($flag, FILTER_VALIDATE_BOOLEAN)
                || $flag === '1'
                || strtolower($flag) === 'yes';
        }

        $appEnv = strtolower(trim((string) (getenv('APP_ENV') ?: 'production')));

        return in_array($appEnv, ['development', 'testing', 'local'], true);
    }
}
