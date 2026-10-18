<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\EncryptionService;
use PaginiumCMS\Core\Settings\Services\SettingsRepository;
use PaginiumCMS\Core\Validation\Validator;

/**
 * SettingsRepository wired for PHPUnit (encryption matches tests/bootstrap.php APP_KEY).
 */
final class SettingsTestHelper
{
    public const TEST_APP_KEY = 'base64:BGtLQwdzAE7ajivCghMa98DyudMghYZEkXKw5PJ/aUE=';

    public static function encryption(): EncryptionService
    {
        return new EncryptionService(self::TEST_APP_KEY);
    }

    public static function repository(string $baseDir, string $file = 'data/settings.json'): SettingsRepository
    {
        $validator = new FileValidator($baseDir);

        return new SettingsRepository(
            new FileWriter($validator),
            StorageTestHelper::localStorage($baseDir),
            new Validator(),
            $file,
            self::encryption()
        );
    }
}
