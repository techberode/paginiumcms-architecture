<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Logging;

use PaginiumCMS\Core\Logging\Models\LogSeverity;
use PaginiumCMS\Core\Logging\Services\ApplicationLogMessageFormatter;
use PHPUnit\Framework\TestCase;

final class ApplicationLogMessageFormatterTest extends TestCase
{
    private ApplicationLogMessageFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new ApplicationLogMessageFormatter();
    }

    public function testFormatsSuccessfulHttpAccess(): void
    {
        $message = $this->formatter->format([
            'severity' => LogSeverity::INFO,
            'category' => 'http_access',
            'message' => 'GET /api/admin/logs/stats 200',
            'context' => [
                'method' => 'GET',
                'path' => '/api/admin/logs/stats',
                'status' => 200,
                'duration_ms' => 775.26,
            ],
        ]);

        $this->assertSame(
            'Úspešný prístup k „štatistiky logov“: GET /api/admin/logs/stats → 200 OK (775 ms)',
            $message
        );
    }

    public function testFormatsHttpWarningForClientError(): void
    {
        $message = $this->formatter->format([
            'severity' => LogSeverity::WARNING,
            'category' => 'http_access',
            'message' => 'GET /api/admin/users 403',
            'context' => [
                'method' => 'GET',
                'path' => '/api/admin/users',
                'status' => 403,
                'duration_ms' => 12.0,
            ],
        ]);

        $this->assertSame(
            'Varovanie pri „správa používateľov“: GET /api/admin/users → 403 Zakázané (12 ms)',
            $message
        );
    }

    public function testFormatsHttpErrorForServerFailure(): void
    {
        $message = $this->formatter->format([
            'severity' => LogSeverity::ERROR,
            'category' => 'http_access',
            'message' => 'POST /api/pages 500',
            'context' => [
                'method' => 'POST',
                'path' => '/api/pages',
                'status' => 500,
                'duration_ms' => 40,
            ],
        ]);

        $this->assertStringContainsString('Chyba servera pri „zoznam stránok“', $message);
        $this->assertStringContainsString('500 Chyba servera', $message);
    }

    public function testEnrichNormalizesSeverity(): void
    {
        $entry = $this->formatter->enrich([
            'severity' => LogSeverity::INFO,
            'category' => 'http_access',
            'message' => 'GET /api/debug/client-event 204',
            'context' => [
                'method' => 'GET',
                'path' => '/api/debug/client-event',
                'status' => 204,
                'duration_ms' => 1.64,
            ],
        ]);

        $this->assertSame('info', $entry['severity']);
        $this->assertStringContainsString('debug udalosť z frontendu', (string) $entry['display_message']);
    }
}
