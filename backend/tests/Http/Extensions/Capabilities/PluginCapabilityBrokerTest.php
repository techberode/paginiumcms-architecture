<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Http\Extensions\Capabilities;

use PHPUnit\Framework\TestCase;
use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Core\FlatFile\Models\Page;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityBroker;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityCatalog;
use PaginiumCMS\Http\Extensions\Capabilities\PluginCapabilityDeniedException;
use PaginiumCMS\Http\Extensions\Capabilities\PluginRuntimeContext;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Support\Lang;

final class PluginCapabilityBrokerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Lang::setLocale('en');
    }

    public function testContentGetRequiresReadCapability(): void
    {
        $page = new Page();
        $page->setSlug('about');
        $page->setTitle('About');
        $page->setStatus('published');
        $page->setAuthor('u1');
        $page->setContent('Hello');

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->with('about', 'page')->willReturn($page);
        $media = $this->createStub(MediaRepositoryInterface::class);
        $broker = new PluginCapabilityBroker($content, $media);

        $runtime = $broker->context('hello-widget', [PluginCapabilityCatalog::CONTENT_READ]);
        $document = $runtime->content()->get('page', 'about');

        $this->assertIsArray($document);
        $this->assertSame('about', $document['slug']);
        $this->assertSame('About', $document['title']);
        $this->assertArrayNotHasKey('path', $document);
    }

    public function testContentGetDeniedWithoutRead(): void
    {
        $content = $this->createStub(ContentRepositoryInterface::class);
        $media = $this->createStub(MediaRepositoryInterface::class);
        $runtime = (new PluginCapabilityBroker($content, $media))
            ->context('locked-plugin', [PluginCapabilityCatalog::ADMIN_UI_EDITOR_BLOCK]);

        $this->expectException(PluginCapabilityDeniedException::class);
        $runtime->content()->get('page', 'about');
    }

    public function testMediaGetDeniedWithoutMediaRead(): void
    {
        $content = $this->createStub(ContentRepositoryInterface::class);
        $media = $this->createStub(MediaRepositoryInterface::class);
        $runtime = (new PluginCapabilityBroker($content, $media))
            ->context('hello-widget', [PluginCapabilityCatalog::CONTENT_READ]);

        $this->expectException(PluginCapabilityDeniedException::class);
        $runtime->media()->get('media/x.png');
    }

    public function testMediaGetReturnsMetadata(): void
    {
        $file = new MediaFile();
        $file->setPath('media/logo.png');
        $file->setFileName('logo.png');
        $file->setUrl('/storage/media/logo.png');
        $file->setSizeBytes(12);
        $file->setMimeType('image/png');
        $file->setAltText('Logo');
        $file->setFolder('');

        $content = $this->createStub(ContentRepositoryInterface::class);
        $media = $this->createMock(MediaRepositoryInterface::class);
        $media->method('findByPath')->with('media/logo.png')->willReturn($file);
        $runtime = (new PluginCapabilityBroker($content, $media))
            ->context('gallery', [PluginCapabilityCatalog::MEDIA_READ]);

        $row = $runtime->media()->get('media/logo.png');
        $this->assertIsArray($row);
        $this->assertSame('logo.png', $row['fileName']);
        $this->assertSame('image/png', $row['mimeType']);
    }

    public function testWriteOwnDeniedForOtherAuthor(): void
    {
        $page = new Page();
        $page->setSlug('mine');
        $page->setAuthor('owner-1');
        $page->setTitle('Old');

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->willReturn($page);
        $content->expects($this->never())->method('save');
        $media = $this->createStub(MediaRepositoryInterface::class);
        $runtime = (new PluginCapabilityBroker($content, $media))
            ->context('writer', [PluginCapabilityCatalog::CONTENT_WRITE_OWN], 'other-user');

        $this->expectException(PluginCapabilityDeniedException::class);
        $runtime->content()->save('page', 'mine', ['title' => 'New']);
    }

    public function testWriteOwnAllowedForMatchingAuthor(): void
    {
        $page = new Page();
        $page->setSlug('mine');
        $page->setAuthor('owner-1');
        $page->setTitle('Old');

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->willReturn($page);
        $content->expects($this->once())->method('save')->with($page);
        $media = $this->createStub(MediaRepositoryInterface::class);
        $runtime = (new PluginCapabilityBroker($content, $media))
            ->context('writer', [PluginCapabilityCatalog::CONTENT_WRITE_OWN], 'owner-1');

        $runtime->content()->save('page', 'mine', ['title' => 'New']);
        $this->assertSame('New', $page->getTitle());
    }

    public function testInvokePassesRuntimeToTwoArgumentHandler(): void
    {
        $content = $this->createStub(ContentRepositoryInterface::class);
        $media = $this->createStub(MediaRepositoryInterface::class);
        $broker = new PluginCapabilityBroker($content, $media);
        $seen = null;
        $handler = static function (array $context, PluginRuntimeContext $runtime) use (&$seen): string {
            $seen = $runtime->pluginId();

            return (string) ($context['slug'] ?? '');
        };

        $result = $broker->invoke(
            $handler,
            ['slug' => 'demo', 'userId' => 'u9'],
            'hello-widget',
            [PluginCapabilityCatalog::CONTENT_READ]
        );

        $this->assertSame('demo', $result);
        $this->assertSame('hello-widget', $seen);
    }

    public function testInvokeKeepsOneArgumentHandlersWorking(): void
    {
        $content = $this->createStub(ContentRepositoryInterface::class);
        $media = $this->createStub(MediaRepositoryInterface::class);
        $broker = new PluginCapabilityBroker($content, $media);
        $handler = static function (array $context): string {
            return 'pong-' . (string) ($context['id'] ?? '');
        };

        $this->assertSame(
            'pong-ping-demo',
            $broker->invoke($handler, ['id' => 'ping-demo'], 'ping-demo', [])
        );
    }
}
