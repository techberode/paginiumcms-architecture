<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Performance;

use PaginiumCMS\Core\Performance\PerformanceRouteLabelResolver;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class PerformanceRouteLabelResolverTest extends TestCase
{
    private PerformanceRouteLabelResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new PerformanceRouteLabelResolver();
    }

    public function testSanitizesSlugSegmentsInFallbackPath(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/articles/my-long-slug-title');

        $label = $this->resolver->resolve($request);

        $this->assertSame('/api/articles/{slug}', $label);
        $this->assertStringNotContainsString('my-long-slug-title', $label);
    }

    public function testSanitizesUuidSegments(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest(
            'GET',
            '/api/admin/users/550e8400-e29b-41d4-a716-446655440000'
        );

        $label = $this->resolver->resolve($request);

        $this->assertSame('/api/admin/users/{id}', $label);
    }

    public function testSanitizesNumericIdSegments(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/media/42');

        $label = $this->resolver->resolve($request);

        $this->assertSame('/api/media/{id}', $label);
    }
}
