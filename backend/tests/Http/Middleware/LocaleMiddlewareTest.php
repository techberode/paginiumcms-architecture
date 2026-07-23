<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Middleware;

use PaginiumCMS\Core\I18n\Services\LocaleScaffoldService;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Middleware\LocaleMiddleware;
use PaginiumCMS\Support\AppTimezone;
use PaginiumCMS\Support\Lang;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

final class LocaleMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Lang::resetForTests();
        parent::tearDown();
    }

    public function testUsesConfiguredLanguageFromSettings(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => match ($key) {
                'general.language' => 'en',
                'general.timezone' => 'Europe/Bratislava',
                'general.timezoneDst' => true,
                default => $default,
            }
        );

        $middleware = new LocaleMiddleware($settings, new SupportedLocalesRegistry());
        $request = (new ServerRequestFactory())->createServerRequest('GET', '/api/content/pages');
        $response = (new ResponseFactory())->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(function () use ($response) {
            $this->assertSame('en', Lang::getLocale());
            $this->assertSame('Content not found', Lang::get('not_found', [], 'content'));

            return $response;
        });

        $middleware->process($request, $handler);
    }

    public function testFallsBackToAcceptLanguageHeader(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => match ($key) {
                'general.language' => 'xx',
                'general.timezone' => AppTimezone::fromEnvironment(),
                'general.timezoneDst' => true,
                default => $default,
            }
        );

        $middleware = new LocaleMiddleware($settings, new SupportedLocalesRegistry());
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/content/pages')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturnCallback(function () {
            $this->assertSame('en', Lang::getLocale());

            return $this->createMock(ResponseInterface::class);
        });

        $middleware->process($request, $handler);
    }
}
