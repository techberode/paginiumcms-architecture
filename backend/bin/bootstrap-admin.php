#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Vytvorí prvého SUPER_ADMIN používateľa, ak ešte neexistuje žiadny účet.
 *
 * Premenné prostredia (voliteľné):
 *   FIRST_ADMIN_EMAIL, FIRST_ADMIN_PASSWORD, FIRST_ADMIN_NAME
 */

use PaginiumCMS\Modules\Security\Contracts\PasswordPolicyInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;

require_once __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$container = $app->getContainer();

/** @var UserRepository $users */
$users = $container->get(UserRepository::class);
/** @var PasswordPolicyInterface $passwordPolicy */
$passwordPolicy = $container->get(PasswordPolicyInterface::class);

$existing = $users->findAll();
if ($existing !== []) {
    fwrite(STDOUT, "Admin bootstrap skipped: " . count($existing) . " user(s) already exist.\n");
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
    $passwordPolicy->requireValid($password);
} catch (\Throwable $e) {
    fwrite(STDERR, 'Invalid FIRST_ADMIN_PASSWORD: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$user = new User();
$user->setEmail($email);
$user->setPassword($password);
$user->setName($name);
$user->setRoles(['SUPER_ADMIN', 'ADMIN', 'EDITOR']);

$users->save($user);

fwrite(STDOUT, "Created first admin user: {$email}\n");
fwrite(STDOUT, "Roles: SUPER_ADMIN, ADMIN, EDITOR\n");
fwrite(STDOUT, "Change the password after first login.\n");

exit(0);
