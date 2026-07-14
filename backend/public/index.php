<?php

declare(strict_types=1);

/**
 * Vstupný bod HTTP API.
 * Načíta skutočnú aplikáciu z bootstrap/app.php (DI, auth, content, admin routes).
 */
require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->run();
