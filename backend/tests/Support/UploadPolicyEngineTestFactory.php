<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\ZipEntryGuard;
use PaginiumCMS\Core\Security\Upload\UploadArchiveValidator;
use PaginiumCMS\Core\Security\Upload\UploadAuditLogger;
use PaginiumCMS\Core\Security\Upload\UploadFilenameGuard;
use PaginiumCMS\Core\Security\Upload\UploadMagicByteInspector;
use PaginiumCMS\Core\Security\Upload\UploadPolicyEngine;
use PaginiumCMS\Core\Security\Upload\UploadPolicyProfile;
use PaginiumCMS\Core\Security\Upload\UploadQuotaGuard;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;

final class UploadPolicyEngineTestFactory
{
    public static function create(
        SettingsRepositoryInterface $settings,
        ?string $storageRoot = null
    ): UploadPolicyEngine {
        $root = $storageRoot ?? sys_get_temp_dir() . '/pag_upload_test_' . uniqid('', true);
        if (!is_dir($root . '/content')) {
            mkdir($root . '/content', 0777, true);
        }

        $validator = new FileValidator($root . '/content');
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        return new UploadPolicyEngine(
            $settings,
            new UploadPolicyProfile($settings),
            new UploadFilenameGuard($settings),
            new UploadMagicByteInspector(),
            new UploadArchiveValidator(new ZipEntryGuard()),
            new UploadQuotaGuard($settings, $reader, $writer),
            new UploadAuditLogger($settings, null)
        );
    }
}
