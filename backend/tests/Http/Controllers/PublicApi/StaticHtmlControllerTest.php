<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Controllers\PublicApi;

use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class StaticHtmlControllerTest extends TestCase
{
    public function testDynamicModeReturns404EvenWhenFileExists(): void
    {
        $slug = 'it48b-dyn-' . substr(uniqid('', true), -8);
        $this->writeCompiledPage($slug, '<!doctype html><title>Hidden</title>');

        try {
            $response = $this->handleRequest($this->createJsonRequest('GET', '/static-html/pages/' . $slug));
            $this->assertSame(404, $response->getStatusCode());
            $this->assertSame('', (string) $response->getBody());
        } finally {
            $this->deleteCompiledPage($slug);
        }
    }

    public function testHybridServesCompiledHtmlAnonymously(): void
    {
        $slug = 'it48b-hyb-' . substr(uniqid('', true), -8);
        $this->enableHybridServe();
        $this->writeCompiledPage($slug, '<!doctype html><html><head><title>Public</title></head><body>Hello static</body></html>');

        try {
            $response = $this->handleRequest($this->createJsonRequest('GET', '/static-html/pages/' . $slug));
            $this->assertSame(200, $response->getStatusCode());
            $body = (string) $response->getBody();
            $this->assertStringContainsString('Hello static', $body);
            $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
            $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
            $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
            $this->assertStringContainsString("default-src 'none'", $response->getHeaderLine('Content-Security-Policy'));
            $this->assertStringNotContainsString('attachment', $response->getHeaderLine('Content-Disposition'));
        } finally {
            $this->deleteCompiledPage($slug);
        }
    }

    public function testReservedLoginSlugStays404(): void
    {
        $this->enableHybridServe();
        $this->writeCompiledPage('login', '<!doctype html><title>Stolen</title>');

        try {
            $response = $this->handleRequest($this->createJsonRequest('GET', '/static-html/pages/login'));
            $this->assertSame(404, $response->getStatusCode());
            $this->assertStringNotContainsString('Stolen', (string) $response->getBody());
        } finally {
            $this->deleteCompiledPage('login');
        }
    }

    public function testTraversalSlugIsNotARoute(): void
    {
        $response = $this->handleRequest($this->createJsonRequest('GET', '/static-html/pages/../etc/passwd'));
        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    public function testHybridServesCompiledArticle(): void
    {
        $slug = 'it48b-art-' . substr(uniqid('', true), -8);
        $this->enableHybridServe();
        $writer = $this->container()->get(FileWriterInterface::class);
        $path = 'static/blog/' . $slug . '/index.html';
        $writer->write($path, '<!doctype html><title>Post</title><p>Article body</p>', false);

        try {
            $response = $this->handleRequest($this->createJsonRequest('GET', '/static-html/blog/' . $slug));
            $this->assertSame(200, $response->getStatusCode());
            $this->assertStringContainsString('Article body', (string) $response->getBody());
        } finally {
            if ($writer->getBasePath() !== '' && is_file($writer->getBasePath() . '/' . $path)) {
                $writer->delete($path, false);
            }
        }
    }

    private function enableHybridServe(): void
    {
        $settings = $this->container()->get(SettingsRepositoryInterface::class);
        $settings->setGroup('engine', array_merge($settings->group('engine'), [
            'renderMode' => 'hybrid',
        ]));
    }

    private function writeCompiledPage(string $slug, string $html): void
    {
        $this->container()->get(FileWriterInterface::class)
            ->write('static/pages/' . $slug . '/index.html', $html, false);
    }

    private function deleteCompiledPage(string $slug): void
    {
        $writer = $this->container()->get(FileWriterInterface::class);
        $path = 'static/pages/' . $slug . '/index.html';
        try {
            $writer->delete($path, false);
        } catch (\Throwable) {
            // already gone
        }
    }
}
