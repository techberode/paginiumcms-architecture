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
require __DIR__ . '/cli-env.php';

use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;

$secret = paginium_dev_unlock_secret();
if ($secret === '') {
    fwrite(STDERR, "Chýba DEV_UNLOCK_SECRET v prostredí.\n");
    fwrite(STDERR, "Nastav v .env (viď .env.example) alebo: export DEV_UNLOCK_SECRET=\"...\"\n");
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
