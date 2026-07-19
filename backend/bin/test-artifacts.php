#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Scan / cleanup test artifacts from integration test runs.
 *
 * Usage:
 *   php backend/bin/test-artifacts.php --scan
 *   php backend/bin/test-artifacts.php --purge
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use PaginiumCMS\Tests\Support\TestStorageCleaner;

/** @var list<string> $args */
$args = $_SERVER['argv'] ?? [];

$purge = in_array('--purge', $args, true);
$scanOnly = in_array('--scan', $args, true) || !$purge;

if ($scanOnly && !$purge) {
    $stats = TestStorageCleaner::scanTestArtifacts();
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

$report = TestStorageCleaner::purgeWithReport();
echo TestStorageCleaner::formatPurgeReport($report) . "\n";

exit(0);
