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
        $this->assertContains('time-entry:manage', PermissionCatalog::ALL);
        $this->assertContains('time-entry:manage', $admin);
        $this->assertContains('time-entry:manage', $editor);
        $this->assertContains('support-ticket:manage', PermissionCatalog::ALL);
        $this->assertContains('support-ticket:manage', $admin);
        $this->assertContains('support-ticket:manage', $editor);
        $this->assertContains('mail:read-own', PermissionCatalog::ALL);
        $this->assertContains('mail:read-own', $admin);
        $this->assertContains('mail:read-all', $admin);
        $this->assertContains('mail:read-own', $editor);
        $this->assertNotContains('mail:read-all', $editor);
    }

    public function testCatalogIncludesThemeStudioPermissions(): void
    {
        $this->assertContains('themes:read', PermissionCatalog::ALL);
        $this->assertContains('themes:edit', PermissionCatalog::ALL);
        $this->assertContains('static:rebuild', PermissionCatalog::ALL);
        $this->assertContains('static:rebuild', PermissionCatalog::defaultRolePermissions()['ADMIN']);
        $admin = PermissionCatalog::defaultRolePermissions()['ADMIN'];
        $this->assertContains('themes:read', $admin);
        $this->assertContains('themes:edit', $admin);
        $editor = PermissionCatalog::defaultRolePermissions()['EDITOR'];
        $this->assertNotContains('themes:read', $editor);
        $this->assertNotContains('themes:edit', $editor);
    }
}
