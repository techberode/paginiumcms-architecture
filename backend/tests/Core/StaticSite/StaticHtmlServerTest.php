<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\StaticSite;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\StaticSite\StaticHtmlServer;
use PaginiumCMS\Core\StaticSite\StaticSitePath;
use PaginiumCMS\Core\StaticSite\StaticSiteSettings;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class StaticHtmlServerTest extends TestCase
{
    public function testHybridServesCompiledPageAndRejectsReservedSlug(): void
    {
        $root = $this->bootTree('hybrid');
        $root['writer']->write('static/pages/about/index.html', '<!doctype html><title>About</title>', false);
        $root['writer']->write('static/pages/login/index.html', '<!doctype html><title>Stolen</title>', false);
        $root['writer']->write('static/blog/hello/index.html', '<!doctype html><title>Hello</title>', false);

        $page = $root['server']->load('page', 'about');
        $this->assertNotNull($page);
        $this->assertStringContainsString('<title>About</title>', $page['html']);
        $this->assertSame('static/pages/about/index.html', $page['path']);

        $this->assertNull($root['server']->load('page', 'login'));
        $this->assertNull($root['server']->load('page', '../etc/passwd'));
        $this->assertNull($root['server']->load('page', 'missing'));

        $article = $root['server']->load('article', 'hello');
        $this->assertNotNull($article);
        $this->assertStringContainsString('<title>Hello</title>', $article['html']);
    }

    public function testDynamicModeNeverServesExistingHtml(): void
    {
        $root = $this->bootTree('dynamic');
        $root['writer']->write('static/pages/about/index.html', '<!doctype html><title>About</title>', false);

        $this->assertFalse($root['server']->isEnabled());
        $this->assertNull($root['server']->load('page', 'about'));
    }

    public function testReservedSlugHelperCoversAdminFirstSegments(): void
    {
        $this->assertTrue(StaticSitePath::isReservedSlug('dashboard'));
        $this->assertTrue(StaticSitePath::isReservedSlug('api'));
        $this->assertTrue(StaticSitePath::isReservedSlug('../x'));
        $this->assertFalse(StaticSitePath::isReservedSlug('about'));
        $this->assertFalse(StaticSitePath::isReservedSlug(StaticSitePath::HOME_SLUG));
    }

    /**
     * @return array{writer: FileWriter, server: StaticHtmlServer}
     */
    private function bootTree(string $mode): array
    {
        vfsStream::setup('static-html', null, ['content' => []]);
        $base = vfsStream::url('static-html/content');
        $validator = new FileValidator($base);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(static function (string $group) use ($mode): array {
            if ($group === 'engine') {
                return ['renderMode' => $mode];
            }

            return ['language' => 'en'];
        });

        return [
            'writer' => $writer,
            'server' => new StaticHtmlServer($reader, new StaticSiteSettings($settings)),
        ];
    }
}
