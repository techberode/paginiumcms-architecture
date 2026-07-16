#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI generátor a registrácia dev unlock tokenov.
 *
 * Použitie (v privátnom repozitári / CI, NIE na produkčnom serveri za behu CMS):
 *
 *   export DEV_UNLOCK_SECRET="your-long-random-secret"
 *   php backend/bin/dev-token.php generate --label="ci-deploy" --days=7
 *   php backend/bin/dev-token.php register --token="pagdev_...."
 *   php backend/bin/dev-token.php list
 *
 * Secret držte v GitHub Secrets; token hash môže byť v gitignored registry
 * alebo sa registruje cez deploy hook.
 */

require __DIR__ . '/../../vendor/autoload.php';

use PaginiumCMS\Core\Developer\DevTokenGenerator;
use PaginiumCMS\Core\Developer\DevTokenRegistry;

$secret = getenv('DEV_UNLOCK_SECRET') ?: '';
if ($secret === '') {
    fwrite(STDERR, "Chýba DEV_UNLOCK_SECRET v prostredí.\n");
    exit(1);
}

/** @var list<string> $cliArgs */
$cliArgs = $_SERVER['argv'] ?? [];

$generator = new DevTokenGenerator($secret);
$registry = new DevTokenRegistry();

$command = $cliArgs[1] ?? 'help';

switch ($command) {
    case 'generate':
        $label = 'developer';
        $days = 7;
        foreach (array_slice($cliArgs, 2) as $arg) {
            if (str_starts_with($arg, '--label=')) {
                $label = substr($arg, 8);
            }
            if (str_starts_with($arg, '--days=')) {
                $days = (int) substr($arg, 7);
            }
        }
        $result = $generator->generate($label, max(3600, $days * 86400));
        echo "Token (ukladajte bezpečne, zobrazí sa len raz):\n";
        echo $result['token'] . "\n\n";
        echo "Hash pre registráciu:\n";
        echo $result['hash'] . "\n";
        echo "Expirácia: " . date('c', $result['expires_at']) . "\n";
        break;

    case 'register':
        $token = null;
        foreach (array_slice($cliArgs, 2) as $arg) {
            if (str_starts_with($arg, '--token=')) {
                $token = substr($arg, 8);
            }
        }
        if ($token === null) {
            fwrite(STDERR, "Použitie: register --token=pagdev_...\n");
            exit(1);
        }
        $validation = $generator->validate($token, new DevTokenRegistry());
        // validate without registry entry fails - register by parsing token
        $parts = explode('.', substr($token, strlen('pagdev_')), 2);
        if (count($parts) !== 2) {
            fwrite(STDERR, "Neplatný token\n");
            exit(1);
        }
        $payload = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $registry->register([
            'hash' => hash('sha256', $token),
            'label' => $payload['label'] ?? 'developer',
            'expires_at' => (int) ($payload['exp'] ?? time() + 86400),
            'single_use' => $payload['single'] ?? true,
        ]);
        echo "Token registrovaný.\n";
        break;

    case 'list':
        foreach ($registry->listRegistered() as $entry) {
            echo sprintf(
                "- %s | exp: %s | used: %s | revoked: %s\n",
                $entry['label'] ?? '?',
                isset($entry['expires_at']) ? date('c', (int) $entry['expires_at']) : '?',
                $entry['used_at'] ?? 'never',
                !empty($entry['revoked']) ? 'yes' : 'no'
            );
        }
        break;

    default:
        echo "Príkazy: generate [--label=] [--days=] | register --token= | list\n";
        break;
}
