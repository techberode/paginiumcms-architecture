<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Core\Content;

use PaginiumCMS\Core\Content\BlogAuthorSettings;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
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

        $service = new BlogAuthorSettings($settings);

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

        $resolved = (new BlogAuthorSettings($settings))->resolveForArticle($article);

        $this->assertSame('Redakcia', $resolved['author']);
        $this->assertSame('Oficiálny blog tímu.', $resolved['authorBio']);
        $this->assertSame('/storage/logo.png', $resolved['authorAvatarUrl']);
        $this->assertTrue($resolved['showAuthorBox']);
    }
}
