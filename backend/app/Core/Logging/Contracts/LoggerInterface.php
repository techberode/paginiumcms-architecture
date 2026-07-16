<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Logging\Contracts;

use PaginiumCMS\Core\Logging\Models\LogEntry;

interface LoggerInterface
{
    /**
     * @param array<int|string, mixed> $context
     */
    public function info(string $message, array $context = []): void;
    /**
     * @param array<int|string, mixed> $context
     */
    public function warning(string $message, array $context = []): void;
    /**
     * @param array<int|string, mixed> $context
     */
    public function error(string $message, array $context = []): void;
    /**
     * @param array<int|string, mixed> $context
     */
    public function critical(string $message, array $context = []): void;
    /**
     * @param array<int|string, mixed> $context
     */
    public function debug(string $message, array $context = []): void; // <-- PRIDANÉ
    /**
     * @param array<int|string, mixed> $context
     */
    public function log(string $severity, string $message, array $context = []): void;
    /**
     * @return array<int|string, mixed>
     */
    public function getLastEntries(int $limit = 100): array;
    /**
     * @return array<int|string, mixed>
     */
    public function getEntriesBySeverity(string $severity, int $limit = 100): array;
    /**
     * @return array<int|string, mixed>
     */
    public function getEntriesByCategory(string $category, int $limit = 100): array;
    public function clearOldEntries(int $days = 30): int;
}
