<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security;

use PaginiumCMS\Modules\Security\PermissionCatalog;
use PHPUnit\Framework\TestCase;

final class PermissionCatalogTest extends TestCase
{
    public function testDecodeAndEncodePermissions(): void
    {
        $encoded = PermissionCatalog::encodePermissions(['content:edit', 'media:upload', 'invalid:perm']);
        $this->assertSame('content:edit,media:upload', $encoded);
        $this->assertSame(['content:edit', 'media:upload'], PermissionCatalog::decodePermissions($encoded));
    }

    public function testSettingsKeyForRole(): void
    {
        $this->assertSame('permissionsAdmin', PermissionCatalog::settingsKeyForRole('ADMIN'));
        $this->assertSame('permissionsEditor', PermissionCatalog::settingsKeyForRole('EDITOR'));
        $this->assertSame('permissionsUser', PermissionCatalog::settingsKeyForRole('USER'));
    }

    public function testCatalogIncludesProjectPlanPermissions(): void
    {
        $this->assertContains('project-plan:read', PermissionCatalog::ALL);
        $this->assertContains('project-plan:manage', PermissionCatalog::ALL);
        $admin = PermissionCatalog::defaultRolePermissions()['ADMIN'];
        $this->assertContains('project-plan:manage', $admin);
        $editor = PermissionCatalog::defaultRolePermissions()['EDITOR'];
        $this->assertContains('project-plan:read', $editor);
        $this->assertContains('project-plan:manage', $editor);
    }

    public function testCatalogIncludesThemeStudioPermissions(): void
    {
        $this->assertContains('themes:read', PermissionCatalog::ALL);
        $this->assertContains('themes:edit', PermissionCatalog::ALL);
        $admin = PermissionCatalog::defaultRolePermissions()['ADMIN'];
        $this->assertContains('themes:read', $admin);
        $this->assertContains('themes:edit', $admin);
        $editor = PermissionCatalog::defaultRolePermissions()['EDITOR'];
        $this->assertNotContains('themes:read', $editor);
        $this->assertNotContains('themes:edit', $editor);
    }
}
