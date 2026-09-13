<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Media\Services;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use PaginiumCMS\Modules\Media\Contracts\S3MediaFilesystemFactoryInterface;

/**
 * Builds a Flysystem operator for S3-compatible media storage (Iteration 72).
 */
final class S3MediaFilesystemFactory implements S3MediaFilesystemFactoryInterface
{
    public function create(S3MediaStorageConfig $config): FilesystemOperator
    {
        $clientConfig = [
            'version' => 'latest',
            'region' => $config->region,
            'credentials' => [
                'key' => $config->keyId,
                'secret' => $config->secret,
            ],
        ];

        if ($config->endpoint !== '') {
            $clientConfig['endpoint'] = $config->endpoint;
            $clientConfig['use_path_style_endpoint'] = $config->pathStyle;
        }

        $client = new S3Client($clientConfig);
        $adapter = new AwsS3V3Adapter($client, $config->bucket);

        return new Filesystem($adapter);
    }
}
