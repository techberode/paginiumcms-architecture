<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\BlogAuthorSettings;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Modules\Security\Services\UserRepository;
use PHPUnit\Framework\TestCase;

final class BlogAuthorSettingsTest extends TestCase
{
    public function testDefaultNameFallsBackToSiteName(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['content', []],
            ['general', ['siteName' => 'Môj web']],
        ]);

        $service = new BlogAuthorSettings($settings, $this->createStub(UserRepository::class));

        $this->assertSame('Môj web', $service->defaultName());
    }

    public function testResolveForArticleUsesSettingsBioWhenArticleHasNoOverride(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['content', [
                'blogAuthorName' => 'Redakcia',
                'blogAuthorBio' => 'Oficiálny blog tímu.',
                'blogAuthorAvatarUrl' => '/storage/logo.png',
                'blogShowAuthorBox' => true,
            ]],
            ['general', []],
        ]);

        $article = new Article();
        $article->setTitle('Test');
        $article->setSlug('test');
        $article->setAuthor('');

        $resolved = (new BlogAuthorSettings($settings, $this->createStub(UserRepository::class)))->resolveForArticle($article);

        $this->assertSame('Redakcia', $resolved['author']);
        $this->assertSame('Oficiálny blog tímu.', $resolved['authorBio']);
        $this->assertSame('/storage/logo.png', $resolved['authorAvatarUrl']);
        $this->assertTrue($resolved['showAuthorBox']);
    }

    public function testResolveForArticleUsesLinkedUserProfile(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['content', [
                'blogAuthorName' => 'Redakcia',
                'blogAuthorBio' => 'Globálne bio.',
                'blogAuthorAvatarUrl' => '/storage/default.png',
            ]],
            ['general', []],
        ]);

        $user = (new User())
            ->setEmail('author@example.com')
            ->setName('Jana Nová')
            ->setBio('Autorka technických článkov.')
            ->setAvatarUrl('media/avatars/jana.webp');

        $users = $this->createMock(UserRepository::class);
        $users->method('findById')->with('user_123')->willReturn($user);

        $article = new Article();
        $article->setSlug('linked-author');
        $article->setAuthorId('user_123');

        $resolved = (new BlogAuthorSettings($settings, $users))->resolveForArticle($article);

        $this->assertSame('Jana Nová', $resolved['author']);
        $this->assertSame('Autorka technických článkov.', $resolved['authorBio']);
        $this->assertSame('media/avatars/jana.webp', $resolved['authorAvatarUrl']);
    }

    public function testSyncStoredAuthorNameCopiesLinkedUserDisplayName(): void
    {
        $user = (new User())
            ->setEmail('author@example.com')
            ->setName('Peter K.');

        $users = $this->createMock(UserRepository::class);
        $users->method('findById')->with('user_abc')->willReturn($user);

        $article = new Article();
        $article->setAuthorId('user_abc');

        $service = new BlogAuthorSettings(
            $this->createStub(SettingsRepositoryInterface::class),
            $users
        );
        $service->syncStoredAuthorName($article);

        $this->assertSame('Peter K.', $article->getAuthor());
    }

    public function testAvatarUrlFallsBackToDefaultPngWhenSettingEmpty(): void
    {
        $settings = $this->createStub(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturnMap([
            ['content', []],
            ['general', []],
        ]);

        $article = new Article();
        $article->setSlug('test');

        $resolved = (new BlogAuthorSettings($settings, $this->createStub(UserRepository::class)))
            ->resolveForArticle($article);

        $this->assertSame('/storage/app/content/media/defaults/author-avatar.png', $resolved['authorAvatarUrl']);
    }
}
