// backend/app/Core/Developer/DeveloperMode.php
<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Developer;

class DeveloperMode
{
    public static function isEnabled(): bool
    {
        return getenv('DEVELOPER_MODE') === 'true';
    }

    public static function verifySecret(string $code): bool
    {
        $secret = getenv('DEVELOPER_SECRET');
        if (empty($secret)) {
            return false;
        }

        // Overenie TOTP kódu
        $totp = \OTPHP\TOTP::create($secret);
        return $totp->verify($code);
    }
}
