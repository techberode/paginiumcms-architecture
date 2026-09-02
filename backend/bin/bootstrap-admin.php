#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Vytvorí prvého SUPER_ADMIN používateľa, ak ešte neexistuje žiadny účet.
 *
 * Premenné prostredia (voliteľné):
 *   FIRST_ADMIN_EMAIL, FIRST_ADMIN_PASSWORD, FIRST_ADMIN_NAME
 */

use PaginiumCMS\Core\Setup\Services\FirstAdminBootstrapService;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$container = $app->getContainer();

/** @var FirstAdminBootstrapService $firstAdmin */
$firstAdmin = $container->get(FirstAdminBootstrapService::class);

if ($firstAdmin->hasUsers()) {
    fwrite(STDOUT, "Admin bootstrap skipped: users already exist.\n");
    exit(0);
}

$email = trim((string) (getenv('FIRST_ADMIN_EMAIL') ?: 'admin@paginium.local'));
$password = (string) (getenv('FIRST_ADMIN_PASSWORD') ?: 'Admin123!ChangeMe');
$name = trim((string) (getenv('FIRST_ADMIN_NAME') ?: 'Administrator'));

if ($email === '' || $name === '') {
    fwrite(STDERR, "FIRST_ADMIN_EMAIL and FIRST_ADMIN_NAME must not be empty.\n");
    exit(1);
}

try {
    $firstAdmin->createFirstAdmin($email, $password, $name);
} catch (\InvalidArgumentException $e) {
    fwrite(STDERR, 'Admin bootstrap failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Admin bootstrap failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Created first admin user: {$email}\n");
fwrite(STDOUT, "Roles: SUPER_ADMIN, ADMIN, EDITOR\n");
fwrite(STDOUT, "Change the password after first login.\n");

exit(0);
