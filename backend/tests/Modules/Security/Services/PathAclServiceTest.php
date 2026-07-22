<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\AclRepository;
use PaginiumCMS\Modules\Security\Services\AuthorizationManager;
use PaginiumCMS\Modules\Security\Services\PathAclService;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

final class PathAclServiceTest extends TestCase
{
    public function testAclRuleAllowsMatchingRole(): void
    {
        vfsStream::setup('root', null, [
            'data' => [
                'security' => [
                    'acl.json' => json_encode([
                        'enabled' => true,
                        'rules' => [[
                            'id' => 'r1',
                            'path' => 'content/pages/finance/*',
                            'roles' => ['EDITOR'],
                            'permissions' => [],
                            'enabled' => true,
                        ]],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $acl = new AclRepository($reader);
        $authz = new AuthorizationManager();
        $service = new PathAclService($acl, $authz);

        $editor = new User();
        $editor->setEmail('editor@test.com');
        $editor->addRole('EDITOR');

        $viewer = new User();
        $viewer->setEmail('viewer@test.com');
        $viewer->addRole('VIEWER');

        $this->assertTrue($service->canAccessPath($editor, 'content/pages/finance/budget'));
        $this->assertFalse($service->canAccessPath($viewer, 'content/pages/finance/budget'));
        $this->assertTrue($service->canAccessPath($viewer, 'content/pages/public/about'));
    }

    public function testStorageRelativePathMatchesAdminGlob(): void
    {
        vfsStream::setup('root', null, [
            'data' => [
                'security' => [
                    'acl.json' => json_encode([
                        'enabled' => true,
                        'rules' => [[
                            'id' => 'r1',
                            'path' => 'content/pages/finance/*',
                            'roles' => ['EDITOR'],
                            'permissions' => [],
                            'enabled' => true,
                        ]],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $acl = new AclRepository($reader);
        $authz = new AuthorizationManager();
        $service = new PathAclService($acl, $authz);

        $editor = new User();
        $editor->setEmail('editor@test.com');
        $editor->addRole('EDITOR');

        $this->assertTrue($service->canAccessPath($editor, 'pages/finance/budget.md'));
        $this->assertSame('content/pages/finance/budget', $service->normalizeStoragePath('pages/finance/budget.md'));
    }

    public function testAclDisabledAllowsAllPaths(): void
    {
        vfsStream::setup('root', null, [
            'data' => [
                'security' => [
                    'acl.json' => json_encode([
                        'enabled' => false,
                        'rules' => [[
                            'id' => 'r1',
                            'path' => 'content/pages/secret/*',
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
        $service = new PathAclService(new AclRepository($reader), new AuthorizationManager());

        $viewer = new User();
        $viewer->addRole('USER');

        $this->assertTrue($service->canAccessPath($viewer, 'pages/secret/report.md'));
    }

    public function testPermissionFallbackGrantsAccessWhenRoleDenied(): void
    {
        vfsStream::setup('root', null, [
            'data' => [
                'security' => [
                    'acl.json' => json_encode([
                        'enabled' => true,
                        'rules' => [[
                            'id' => 'r1',
                            'path' => 'content/pages/reports/*',
                            'roles' => [],
                            'permissions' => ['content:edit'],
                            'enabled' => true,
                        ]],
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ]);

        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $service = new PathAclService(new AclRepository($reader), new AuthorizationManager());

        $editor = new User();
        $editor->addRole('EDITOR');

        $this->assertTrue($service->canAccessPath($editor, 'pages/reports/q1.md', 'content:edit'));
    }

    public function testMediaPathNormalizationAddsContentPrefix(): void
    {
        vfsStream::setup('root');
        $reader = $this->createMock(FileReaderInterface::class);
        $reader->method('getBasePath')->willReturn(vfsStream::url('root'));
        $service = new PathAclService(new AclRepository($reader), new AuthorizationManager());

        $this->assertSame(
            'content/media/private/logo',
            $service->normalizeStoragePath('media/private/logo.png')
        );
    }
}
