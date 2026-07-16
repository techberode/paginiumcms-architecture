<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Security;

use PaginiumCMS\Core\Security\SecurityHeaders;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ResponseFactory;

final class SecurityHeadersTest extends TestCase
{
    public function testBuildsExpectedHeaderSet(): void
    {
        $headers = new SecurityHeaders();
        $map = $headers->getHeaders();

        $this->assertArrayHasKey('Strict-Transport-Security', $map);
        $this->assertArrayHasKey('Content-Security-Policy', $map);
        $this->assertArrayHasKey('X-Frame-Options', $map);
        $this->assertStringContainsString("default-src 'self'", (string) $map['Content-Security-Policy']);
        $this->assertStringContainsString("frame-ancestors 'none'", (string) $map['Content-Security-Policy']);
    }

    public function testApplyToResponseAddsHeaders(): void
    {
        $headers = new SecurityHeaders();
        $response = (new ResponseFactory())->createResponse(200);

        $secured = $headers->applyToResponse($response);

        $this->assertSame('DENY', $secured->getHeaderLine('X-Frame-Options'));
        $this->assertSame('nosniff', $secured->getHeaderLine('X-Content-Type-Options'));
    }
}
