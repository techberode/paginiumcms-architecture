<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Modules\Security\PermissionCatalog;
use PaginiumCMS\Support\JsonHelper;
use InvalidArgumentException;

/**
 * Sync path ACL flat-file store from settings accessControl group.
 */
final class AccessControlSyncService
{
    public function __construct(
        private AclRepository $acl
    ) {
    }

    /**
     * @param array<string, mixed> $accessControl
     */
    public function syncPathAclFromSettings(array $accessControl): void
    {
        $enabled = ($accessControl['pathAclEnabled'] ?? false) === true;
        $rulesJson = (string) ($accessControl['pathAclRulesJson'] ?? '[]');
        $decoded = JsonHelper::decode($rulesJson);

        if (!array_is_list($decoded)) {
            throw new InvalidArgumentException('Path ACL pravidlá musia byť JSON pole.');
        }

        $rules = [];
        foreach ($decoded as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $rules[] = $rule;
        }

        $this->acl->save($enabled, $rules);
    }

    /**
     * @return array<string, mixed>
     */
    public function pathAclSettingsFromRepository(): array
    {
        return [
            'pathAclEnabled' => $this->acl->isEnabled(),
            'pathAclRulesJson' => JsonHelper::encode($this->acl->rules()),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function normalizeAccessControlPayload(array $payload): array
    {
        $normalized = $payload;

        foreach (PermissionCatalog::configurableRoles() as $role) {
            $key = PermissionCatalog::settingsKeyForRole($role);
            $encoded = (string) ($payload[$key] ?? '');
            $permissions = PermissionCatalog::normalizeList(
                PermissionCatalog::decodePermissions($encoded)
            );
            $normalized[$key] = PermissionCatalog::encodePermissions($permissions);
        }

        if (isset($payload['pathAclRulesJson'])) {
            $decoded = JsonHelper::decode((string) $payload['pathAclRulesJson']);
            if (!array_is_list($decoded)) {
                throw new InvalidArgumentException('Path ACL pravidlá musia byť JSON pole.');
            }
            $normalized['pathAclRulesJson'] = JsonHelper::encode($decoded);
        }

        return $normalized;
    }
}
