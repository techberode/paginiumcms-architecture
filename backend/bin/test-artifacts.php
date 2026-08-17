#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Scan / cleanup test artifacts from integration test runs.
 *
 * Usage:
 *   php backend/bin/test-artifacts.php --scan
 *   php backend/bin/test-artifacts.php --purge
 *
 * Purge uses prefix-only rules (qa-*, PHPUnit patterns, @example.com) via dev:hygiene.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PaginiumCMS\Support\DevStorageHygiene;

/** @var list<string> $args */
$args = $_SERVER['argv'] ?? [];

$purge = in_array('--purge', $args, true);
$scanOnly = in_array('--scan', $args, true) || !$purge;

if ($scanOnly && !$purge) {
    $stats = DevStorageHygiene::scan();
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

try {
    DevStorageHygiene::assertAllowedEnvironment(false);
} catch (\Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$report = DevStorageHygiene::purge(includeLogs: false, rebuildIndex: true);
echo DevStorageHygiene::formatReport($report) . "\n";

exit(0);
