<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Contracts;

use League\Flysystem\FilesystemOperator;
use PaginiumCMS\Modules\Media\Services\S3MediaStorageConfig;

interface S3MediaFilesystemFactoryInterface
{
    public function create(S3MediaStorageConfig $config): FilesystemOperator;
}
