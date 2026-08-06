<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\LocaleResolver;
use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Tests\Http\TestCase;

final class LocaleResolverTest extends TestCase
{
    public function testExplicitQueryLocaleWins(): void
    {
        $resolver = $this->resolver(['localeFallbackEnabled' => true]);

        $request = $this->createJsonRequest('GET', '/api/pages/home?locale=en');
        $resolution = $resolver->resolveForRequest($request, ['sk', 'en'], 'sk');

        $this->assertSame('en', $resolution->requested);
        $this->assertSame('en', $resolution->resolved);
        $this->assertFalse($resolution->fallback);
    }

    public function testMissingLocaleFallsBackToResourceDefault(): void
    {
        $resolver = $this->resolver(['localeFallbackEnabled' => true]);

        $request = $this->createJsonRequest('GET', '/api/pages/home?locale=en');
        $resolution = $resolver->resolveForRequest($request, ['sk'], 'sk');

        $this->assertSame('en', $resolution->requested);
        $this->assertSame('sk', $resolution->resolved);
        $this->assertTrue($resolution->fallback);
    }

    public function testAcceptLanguageUsedWhenNegotiationEnabled(): void
    {
        $resolver = $this->resolver([
            'localeFallbackEnabled' => true,
            'localeNegotiationEnabled' => true,
        ]);

        $request = $this->createJsonRequest('GET', '/api/pages/home', null, [
            'Accept-Language' => 'en-US,en;q=0.9',
        ]);
        $resolution = $resolver->resolveForRequest($request, ['sk', 'en'], 'sk');

        $this->assertSame('en', $resolution->resolved);
        $this->assertFalse($resolution->fallback);
    }

    /**
     * @param array<string, mixed> $contentSettings
     */
    private function resolver(array $contentSettings = []): LocaleResolver
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('get')->willReturnCallback(function (string $key, mixed $default = null): mixed {
            if ($key === 'general.language') {
                return 'sk';
            }

            return $default;
        });
        $settings->method('group')->willReturnCallback(function (string $group) use ($contentSettings): array {
            if ($group === 'content') {
                return array_merge([
                    'localeFallbackEnabled' => true,
                    'localeNegotiationEnabled' => true,
                ], $contentSettings);
            }

            return [];
        });

        return new LocaleResolver(new SupportedLocalesRegistry(), $settings);
    }
}
