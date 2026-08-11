<?php

declare(strict_types=1);

// PHPUnit must never inherit host/demo .env flags (CI runners, homelab demo stacks).
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
putenv('DEMO_MODE=false');
$_ENV['DEMO_MODE'] = 'false';
$_SERVER['DEMO_MODE'] = 'false';

// Encryption at-rest (webhook secrets, TOTP, settings passwords) in PHPUnit — .env is skipped when APP_ENV=testing.
putenv('APP_KEY=base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=');
$_ENV['APP_KEY'] = 'base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=';
$_SERVER['APP_KEY'] = 'base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=';

// 1. Načítanie UTF-8 nastavení (definuje utf8_normalize())
require_once __DIR__ . '/../backend/bootstrap/utf8.php';

// 2. Načítanie autoloaderu
require_once __DIR__ . '/../vendor/autoload.php';

// 3. Manuálne načítanie chýbajúcich rozhraní (ak treba)
if (!interface_exists('PaginiumCMS\Core\FlatFile\Contracts\MarkdownContentParserInterface')) {
    require_once __DIR__ . '/../backend/app/Core/FlatFile/Contracts/MarkdownContentParserInterface.php';
}
