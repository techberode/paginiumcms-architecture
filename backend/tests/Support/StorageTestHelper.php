<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Support;

use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Storage\Contracts\StorageInterface;
use PaginiumCMS\Core\Storage\Drivers\LocalFlatFileStorage;

final class StorageTestHelper
{
    public static function localStorage(string $baseDir): StorageInterface
    {
        $validator = new FileValidator($baseDir);

        return new LocalFlatFileStorage(
            new FileReader($validator),
            new FileWriter($validator),
            $validator
        );
    }
}
