<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\Content\AvatarImageProcessor;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PHPUnit\Framework\TestCase;

final class UserAvatarServiceTest extends TestCase
{
    private AvatarImageProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new AvatarImageProcessor();
    }

    public function testAssignFromMediaUrlAcceptsStoragePath(): void
    {
        $media = new MediaFile();
        $media->setPath('media/avatars/user_1/photo.png');
        $media->setMimeType('image/png');
        $media->setUrl('/storage/app/content/media/avatars/user_1/photo.png');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findByPath')
            ->with('media/avatars/user_1/photo.png')
            ->willReturn($media);

        $service = new UserAvatarService($repo, $this->processor);
        $url = $service->assignFromMediaUrl('/storage/app/content/media/avatars/user_1/photo.png');

        $this->assertSame('/storage/app/content/media/avatars/user_1/photo.png', $url);
    }

    public function testAssignFromMediaUrlRejectsUnknownPath(): void
    {
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->expects($this->never())->method('findByPath');

        $service = new UserAvatarService($repo, $this->processor);

        $this->expectException(FlatFileException::class);
        $service->assignFromMediaUrl('https://evil.example/photo.png');
    }

    public function testAssignFromMediaUrlRejectsNonImageMime(): void
    {
        $media = new MediaFile();
        $media->setPath('media/docs/manual.pdf');
        $media->setMimeType('application/pdf');
        $media->setUrl('/storage/app/content/media/docs/manual.pdf');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->method('findByPath')->willReturn($media);

        $service = new UserAvatarService($repo, $this->processor);

        $this->expectException(FlatFileException::class);
        $service->assignFromMediaUrl('/storage/app/content/media/docs/manual.pdf');
    }

    public function testRemoveClearsAvatarUrl(): void
    {
        $user = new User();
        $user->setAvatarUrl('/storage/app/content/media/avatars/user_1/photo.png');

        $service = new UserAvatarService($this->createMock(MediaRepositoryInterface::class), $this->processor);
        $service->remove($user);

        $this->assertNull($user->getAvatarUrl());
    }
}
