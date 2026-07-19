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
}
