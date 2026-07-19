#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * ISS-002 ops helper — rebuild flat-file content index + purge list cache.
 *
 * Equivalent: php backend/bin/console content:cache-purge --reindex
 */

passthru(
    escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/console') . ' content:cache-purge --reindex',
    $exitCode
);

exit($exitCode);
