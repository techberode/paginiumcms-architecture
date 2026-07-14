#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Registrácia offline dev tokenu (HMAC overenie + zápis hash do registry).
 *
 *   export DEV_UNLOCK_SECRET="..."
 *   php backend/bin/dev-token-register.php pagdev_....
 */

require __DIR__ . '/../../vendor/autoload.php';

use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;

$secret = getenv('DEV_UNLOCK_SECRET') ?: '';
if ($secret === '') {
    fwrite(STDERR, "Chýba DEV_UNLOCK_SECRET v prostredí.\n");
    exit(1);
}

$token = $argv[1] ?? '';
if ($token === '') {
    fwrite(STDERR, "Použitie: dev-token-register.php pagdev_....\n");
    exit(1);
}

try {
    $generator = new DevTokenGenerator($secret);
    $registry = new DevTokenRegistry();
    $registry->registerFromToken($generator, $token);
    echo "Token registrovaný.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
