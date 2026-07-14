<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Contracts;

use PaginiumCMS\Core\Logging\Models\LogEntry;

interface LoggerInterface
{
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
    public function debug(string $message, array $context = []): void; // <-- PRIDANÉ
    public function log(string $severity, string $message, array $context = []): void;
    public function getLastEntries(int $limit = 100): array;
    public function getEntriesBySeverity(string $severity, int $limit = 100): array;
    public function getEntriesByCategory(string $category, int $limit = 100): array;
    public function clearOldEntries(int $days = 30): int;
}
