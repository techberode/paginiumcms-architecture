<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\AuditTrail\Services;

use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Core\Logging\Contracts\LoggerInterface;
use PaginiumCMS\Core\Versioning\Services\EnhancedVersionManager;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class AuditTrailServiceTest extends TestCase
{
    public function testExportAuditToCsvSanitizesAllCells(): void
    {
        $logs = [[
            'timestamp' => "2026-07-23 12:00:00\r\nextra",
            'category' => 'audit_content_change',
            'severity' => 'INFO',
            'message' => '[CONTENT_CHANGE] Update article: blog',
            'context' => [
                'action' => "update\r\naction",
                'target' => "page\r\ninjected",
                'user' => [
                    'name' => "=CMD('calc')",
                    'email' => "evil\r\n@example.com",
                ],
            ],
        ]];

        $service = new AuditTrailService(
            $this->createLoggerStub($logs),
            $this->createStub(EnhancedVersionManager::class),
            $this->createStub(UserRepository::class),
        );

        $csv = $service->exportAuditToCsv();

        $this->assertStringContainsString('"2026-07-23 12:00:00 extra"', $csv);
        $this->assertStringContainsString('"=CMD(\'calc\')"', $csv);
        $this->assertStringContainsString('"evil @example.com"', $csv);
        $this->assertStringNotContainsString("\r\nextra", $csv);
        $this->assertStringNotContainsString("page\r\ninjected", $csv);
        $this->assertSame(2, substr_count($csv, "\n"));
    }

    /**
     * @param list<array<string, mixed>> $logs
     */
    private function createLoggerStub(array $logs): LoggerInterface
    {
        return new class($logs) implements LoggerInterface {
            /** @param list<array<string, mixed>> $logs */
            public function __construct(private array $logs)
            {
            }

            public function info(string $message, array $context = []): void
            {
            }

            public function warning(string $message, array $context = []): void
            {
            }

            public function error(string $message, array $context = []): void
            {
            }

            public function critical(string $message, array $context = []): void
            {
            }

            public function debug(string $message, array $context = []): void
            {
            }

            public function log(string $severity, string $message, array $context = []): void
            {
            }

            public function writeEntry(\PaginiumCMS\Core\Logging\Models\LogEntry $entry): void
            {
            }

            public function getLastEntries(int $limit = 100): array
            {
                return $this->logs;
            }

            public function getEntriesBySeverity(string $severity, int $limit = 100): array
            {
                return [];
            }

            public function getEntriesByCategory(string $category, int $limit = 100): array
            {
                return [];
            }

            public function clearOldEntries(int $days = 30): int
            {
                return 0;
            }
        };
    }
}
