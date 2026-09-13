<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Media\Services;

use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\FilesystemOperator;
use PaginiumCMS\Core\FlatFile\Services\FileReader;
use PaginiumCMS\Core\FlatFile\Services\FileValidator;
use PaginiumCMS\Core\FlatFile\Services\FileWriter;
use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Security\Services\UploadSecurityValidator;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Media\Contracts\S3MediaFilesystemFactoryInterface;
use PaginiumCMS\Modules\Media\Exception\MediaMigrationException;
use PaginiumCMS\Modules\Media\Services\MediaImageOptimizer;
use PaginiumCMS\Modules\Media\Services\MediaMigrationJournalStore;
use PaginiumCMS\Modules\Media\Services\MediaMigrationService;
use PaginiumCMS\Modules\Media\Services\MediaOptimizePreviewStore;
use PaginiumCMS\Modules\Media\Services\MediaRepository;
use PaginiumCMS\Modules\Media\Services\MediaStorageCapabilityProbe;
use PaginiumCMS\Modules\Media\Services\MediaStorageFactory;
use PaginiumCMS\Modules\Media\Services\MediaUrlResolver;
use PaginiumCMS\Modules\Media\Services\S3MediaStorageConfig;
use PaginiumCMS\Tests\Support\UploadPolicyEngineTestFactory;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

final class MediaMigrationServiceTest extends TestCase
{
    private const PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private MediaMigrationService $migration;

    private MediaRepository $repository;

    /** @var array<string, mixed> */
    private array $mediaSettings;

    private InMemoryS3MediaFilesystemFactory $s3FilesystemFactory;

    private MediaStorageFactory $storageFactory;

    protected function setUp(): void
    {
        vfsStream::setup('storage', null, ['content' => []]);
        $root = vfsStream::url('storage/content');
        $validator = new FileValidator($root);
        $reader = new FileReader($validator);
        $writer = new FileWriter($validator);

        $this->mediaSettings = [
            'storageDriver' => 'local',
            's3Bucket' => 'media-bucket',
            's3Region' => 'eu-central-1',
            's3KeyId' => 'AKIAEXAMPLE',
            's3Secret' => 'secret',
            's3Visibility' => 'private',
            'allowedMimeTypes' => 'image/png',
            'maxUploadSizeKb' => 5120,
        ];

        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnCallback(function (string $group): array {
            if ($group === 'media') {
                return $this->mediaSettings;
            }

            if ($group === 'uploadSecurity') {
                return ['unifiedPolicyEnabled' => false];
            }

            return [];
        });
        $settings->method('setGroup')->willReturnCallback(function (string $group, array $values): array {
            if ($group === 'media') {
                $this->mediaSettings = array_merge($this->mediaSettings, $values);
            }

            return $values;
        });

        $this->s3FilesystemFactory = new InMemoryS3MediaFilesystemFactory();
        $this->storageFactory = new MediaStorageFactory(
            $reader,
            $writer,
            $settings,
            new OutboundUrlGuard(true, true),
            $this->s3FilesystemFactory,
            new MediaUrlResolver(),
        );
        $probe = new MediaStorageCapabilityProbe($this->storageFactory, new OutboundUrlGuard(true, true));

        $policyEngine = UploadPolicyEngineTestFactory::create($settings, $root);
        $uploadSecurity = new UploadSecurityValidator($settings, $policyEngine);

        $this->repository = new MediaRepository(
            $reader,
            $writer,
            $settings,
            $uploadSecurity,
            $policyEngine,
            $this->storageFactory,
            new MediaImageOptimizer(),
            new MediaOptimizePreviewStore($reader, $writer),
        );

        $this->migration = new MediaMigrationService(
            $this->repository,
            $this->storageFactory,
            $probe,
            new MediaMigrationJournalStore($reader, $writer),
            $settings,
        );
    }

    public function testFullMigrationCopyVerifyCutoverAndRollback(): void
    {
        $binary = base64_decode(self::PNG_BASE64, true);
        $this->assertIsString($binary);
        $media = $this->repository->saveUpload(
            'photo.png',
            $binary,
            'image/png',
        );
        $originalUrl = $media->getUrl();

        $start = $this->migration->start('local', 's3', 'test_mig_1');
        $this->assertSame('test_mig_1', $start['migrationId']);

        $copy = $this->migration->copyBatch('test_mig_1', 10);
        $this->assertTrue($copy['complete']);
        $this->assertSame(1, $copy['copied']);

        $verify = $this->migration->verify('test_mig_1');
        $this->assertTrue($verify['allVerified']);

        $cutover = $this->migration->cutover('test_mig_1', true);
        $this->assertSame('s3', $cutover['activeDriver']);
        $this->assertSame('s3', $this->mediaSettings['storageDriver']);

        $updated = $this->repository->findByPath($media->getPath());
        $this->assertNotNull($updated);
        $this->assertStringStartsWith('/api/media/file/', $updated->getUrl());
        $this->assertNotSame($originalUrl, $updated->getUrl());

        $rollback = $this->migration->rollback('test_mig_1', true);
        $this->assertSame('local', $rollback['activeDriver']);
        $this->assertSame(1, $rollback['restoredUrls']);
        $this->assertSame(
            'local',
            MediaStorageFactory::driverFromMediaSettings($this->mediaSettings, new OutboundUrlGuard(true, true))
        );

        $restored = $this->repository->findByPath($media->getPath());
        $this->assertNotNull($restored);
        $this->assertSame($originalUrl, $restored->getUrl());
        $this->assertFalse($this->storageFactory->create('s3', false, $this->mediaSettings)->exists($media->getPath()));
    }

    public function testResumeCopySkipsAlreadyCopiedItems(): void
    {
        $binary = base64_decode(self::PNG_BASE64, true);
        $this->assertIsString($binary);
        $this->repository->saveUpload('one.png', $binary, 'image/png');
        $this->repository->saveUpload('two.png', $binary, 'image/png');

        $this->migration->start('local', 's3', 'test_mig_2');
        $first = $this->migration->copyBatch('test_mig_2', 1);
        $this->assertSame(1, $first['copied']);
        $this->assertSame(1, $first['pending']);

        $second = $this->migration->copyBatch('test_mig_2', 1);
        $this->assertSame(1, $second['copied']);
        $this->assertSame(0, $second['pending']);
    }

    public function testCutoverRequiresConfirmation(): void
    {
        $this->expectException(MediaMigrationException::class);
        $this->migration->cutover('missing', false);
    }
}

final class InMemoryS3MediaFilesystemFactory implements S3MediaFilesystemFactoryInterface
{
    private ?FilesystemOperator $filesystem = null;

    public function create(S3MediaStorageConfig $config): FilesystemOperator
    {
        return $this->filesystem ??= new Filesystem(new InMemoryFilesystemAdapter());
    }
}
