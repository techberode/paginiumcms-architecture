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
}
