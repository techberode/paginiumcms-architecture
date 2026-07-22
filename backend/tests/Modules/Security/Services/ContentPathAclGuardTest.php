<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\AclRepository;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\ContentPathAclGuard;
use PaginiumCMS\Modules\Security\Services\PathAclService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class ContentPathAclGuardTest extends TestCase
{
    public function testContentPathFromSlugUsesBlogDirectoryForArticles(): void
    {
        vfsStream::setup('root');
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $guard = new ContentPathAclGuard(new PathAclService(new AclRepository($reader), new AuthorizationManager()));

        $this->assertSame('pages/about', $guard->contentPathFromSlug('page', 'about'));
        $this->assertSame('blog/news', $guard->contentPathFromSlug('article', 'news'));
    }

    public function testRequireAccessThrowsForRestrictedPath(): void
    {
        vfsStream::setup('root', null, [
            'data' => [
                'security' => [
                    'acl.json' => json_encode([
                        'enabled' => true,
                        'rules' => [[
                            'id' => 'r1',
                            'path' => 'content/media/private/*',
                            'roles' => ['ADMIN'],
                            'permissions' => [],
                            'enabled' => true,
                        ]],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $guard = new ContentPathAclGuard(new PathAclService(new AclRepository($reader), new AuthorizationManager()));

        $editor = new User();
        $editor->addRole('EDITOR');

        $this->expectException(\PaginiumCMS\Modules\Security\Exception\AuthorizationException::class);
        $guard->requireAccess($editor, 'media/private/logo.png', 'media:upload');
    }

    public function testCanAccessAllowsWhenAclDisabled(): void
    {
        vfsStream::setup('root', null, [
            'data' => [
                'security' => [
                    'acl.json' => json_encode([
                        'enabled' => false,
                        'rules' => [[
                            'id' => 'r1',
                            'path' => 'content/pages/locked/*',
                            'roles' => ['ADMIN'],
                            'permissions' => [],
                            'enabled' => true,
                        ]],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $guard = new ContentPathAclGuard(new PathAclService(new AclRepository($reader), new AuthorizationManager()));

        $viewer = new User();
        $viewer->addRole('USER');

        $this->assertTrue($guard->canAccess($viewer, 'pages/locked/page.md'));
    }

    public function testMediaFolderPathUsesMediaRootForEmptyFolder(): void
    {
        vfsStream::setup('root');
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $guard = new ContentPathAclGuard(new PathAclService(new AclRepository($reader), new AuthorizationManager()));

        $this->assertSame('media', $guard->mediaFolderPath(''));
        $this->assertSame('media/stock', $guard->mediaFolderPath('stock'));
    }
}
