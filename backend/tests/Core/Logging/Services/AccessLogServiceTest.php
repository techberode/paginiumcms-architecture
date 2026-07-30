<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging\Services;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Logging\Services\AccessLogService;
use PaginiumCMS\Core\Logging\Services\LogWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Support\FileHelper;

final class AccessLogServiceTest extends TestCase
{
    private string $logDir;

    private AccessLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $structure = ['logs' => ['app' => []]];
        vfsStream::setup('storage', null, $structure);
        $this->logDir = vfsStream::url('storage') . '/logs/app';

        $validator = new FileValidator(vfsStream::url('storage') . '/logs');
        $writer = new LogWriter(new FileReader($validator), new FileWriter($validator), $this->logDir);

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([
            'enabled' => true,
            'requestLogging' => true,
            'minSeverity' => LogSeverity::DEBUG,
            'slowRequestMs' => 2000,
            'logAuthEndpoints' => false,
            'retentionDays' => 30,
        ]);

        $this->service = new AccessLogService($writer, $settings);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readEntries(): array
    {
        $path = $this->logDir . '/' . date('Y-m-d') . '.json';
        if (!is_file($path)) {
            return [];
        }

        return array_values(FileHelper::readJson($path));
    }

    public function testSkipsExcludedDebugClientEvent(): void
    {
        $this->service->logRequest('127.0.0.1', 'POST', '/api/debug/client-event', 204, 1.2);
        $this->assertSame([], $this->readEntries());
    }

    public function testSkipsAdminLogsMetaEndpoints(): void
    {
        $this->service->logRequest('127.0.0.1', 'GET', '/api/admin/logs', 200, 5.0);
        $this->service->logRequest('127.0.0.1', 'GET', '/api/admin/logs/stats', 200, 5.0);
        $this->assertSame([], $this->readEntries());
    }

    public function testNotFoundIsInfoNotWarning(): void
    {
        $this->service->logRequest('127.0.0.1', 'GET', '/api/pages/missing', 404, 3.0);
        $entries = $this->readEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(LogSeverity::INFO, $entries[0]['severity'] ?? null);
        $this->assertSame('http_access', $entries[0]['category'] ?? null);
    }

    public function testClientAuthFailureIsWarning(): void
    {
        $this->service->logRequest('127.0.0.1', 'GET', '/api/admin/users', 403, 2.0);
        $entries = $this->readEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(LogSeverity::WARNING, $entries[0]['severity'] ?? null);
    }

    public function testUnauthorizedIsInfoNotWarning(): void
    {
        $this->service->logRequest('127.0.0.1', 'GET', '/api/auth/me', 401, 1.0);
        $entries = $this->readEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(LogSeverity::INFO, $entries[0]['severity'] ?? null);
    }

    public function testServerErrorIsError(): void
    {
        $this->service->logRequest('127.0.0.1', 'GET', '/api/seo/article/x', 500, 2.0);
        $entries = $this->readEntries();
        $this->assertCount(1, $entries);
        $this->assertSame(LogSeverity::ERROR, $entries[0]['severity'] ?? null);
    }
}
