<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Security\Services;

use PaginiumCMS\Core\Content\AvatarImageProcessor;
use PaginiumCMS\Core\FlatFile\Exception\FlatFileException;
use PaginiumCMS\Core\FlatFile\Models\MediaFile;
use PaginiumCMS\Modules\Media\Contracts\MediaRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Services\UserAvatarService;
use PaginiumCMS\Tests\Support\UploadPolicyEngineTestFactory;
use PHPUnit\Framework\TestCase;

final class UserAvatarServiceTest extends TestCase
{
    private AvatarImageProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new AvatarImageProcessor();
    }

    public function testAssignFromMediaUrlProcessesLargeLibraryImage(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(900, 700);
        $this->assertNotFalse($image);
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();

        $media = new MediaFile();
        $media->setPath('media/hero.png');
        $media->setMimeType('image/png');
        $media->setUrl('/storage/app/content/media/hero.png');

        $saved = new MediaFile();
        $saved->setUrl('/storage/app/content/media/avatars/user_1/avatar.png');

        $user = new User();
        $user->setName('Tester');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findByPath')
            ->with('media/hero.png')
            ->willReturn($media);
        $repo->expects($this->once())
            ->method('readBinary')
            ->with('media/hero.png')
            ->willReturn($binary);
        $repo->expects($this->once())
            ->method('saveUpload')
            ->willReturn($saved);

        $service = new UserAvatarService($repo, $this->processor, $this->policyEngine());
        $url = $service->assignFromMediaUrl($user, '/storage/app/content/media/hero.png');

        $this->assertSame('/storage/app/content/media/avatars/user_1/avatar.png', $url);
    }

    public function testAssignFromMediaUrlRejectsUnknownPath(): void
    {
        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->expects($this->never())->method('findByPath');

        $service = new UserAvatarService($repo, $this->processor, $this->policyEngine());
        $user = new User();

        $this->expectException(FlatFileException::class);
        $service->assignFromMediaUrl($user, 'https://evil.example/photo.png');
    }

    public function testAssignFromUploadAcceptsUserIdWithDot(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension not available.');
        }

        $image = imagecreatetruecolor(64, 64);
        $this->assertNotFalse($image);
        ob_start();
        imagepng($image);
        $binary = ob_get_clean();

        $user = new User();
        $reflection = new \ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($user, 'user_abc123.456789');
        $user->setName('Tester');

        $saved = new MediaFile();
        $saved->setUrl('/storage/app/content/media/avatars/user_abc123_456789/avatar.png');

        $repo = $this->createMock(MediaRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('saveUpload')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                'avatars/user_abc123_456789',
                $user->getId()
            )
            ->willReturn($saved);

        $service = new UserAvatarService($repo, $this->processor, $this->policyEngine());
        $url = $service->assignFromUpload($user, 'avatar.png', $binary, 'image/png');

        $this->assertSame('/storage/app/content/media/avatars/user_abc123_456789/avatar.png', $url);
    }

    public function testRemoveClearsAvatarUrl(): void
    {
        $user = new User();
        $user->setAvatarUrl('/storage/app/content/media/avatars/user_1/photo.png');

        $service = new UserAvatarService(
            $this->createMock(MediaRepositoryInterface::class),
            $this->processor,
            $this->policyEngine()
        );
        $service->remove($user);

        $this->assertNull($user->getAvatarUrl());
    }

    private function policyEngine(): \PaginiumCMS\Core\Security\Upload\UploadPolicyEngine
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn(['unifiedPolicyEnabled' => false]);

        return UploadPolicyEngineTestFactory::create($settings);
    }
}
