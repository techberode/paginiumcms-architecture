<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Comments\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Services\CommentPolicyResolver;
use PHPUnit\Framework\TestCase;

class CommentPolicyResolverTest extends TestCase
{
    public function testArticleOverridesGlobalSettings(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->with('comments')->willReturn([
            'enabled' => true,
            'requireApproval' => true,
            'allowGuestComments' => true,
        ]);

        $article = new Article();
        $article->setSlug('demo-post');
        $article->setTitle('Demo');
        $article->setContent('Body');
        $article->setCommentsEnabled(true);
        $article->setCommentsRequireApproval(false);
        $article->setCommentsAllowGuests(false);

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->with('demo-post', 'article')->willReturn($article);

        $resolver = new CommentPolicyResolver($settings, $content);
        $policy = $resolver->resolveForArticle('demo-post');

        $this->assertTrue($policy['enabled']);
        $this->assertFalse($policy['requireApproval']);
        $this->assertFalse($policy['allowGuestComments']);
    }

    public function testDisabledOnArticleBlocksComments(): void
    {
        $settings = $this->createMock(SettingsRepositoryInterface::class);
        $settings->method('group')->willReturn([
            'enabled' => true,
            'requireApproval' => false,
            'allowGuestComments' => true,
        ]);

        $article = new Article();
        $article->setSlug('locked');
        $article->setTitle('Locked');
        $article->setContent('Body');
        $article->setCommentsEnabled(false);

        $content = $this->createMock(ContentRepositoryInterface::class);
        $content->method('findBySlug')->willReturn($article);

        $resolver = new CommentPolicyResolver($settings, $content);
        $policy = $resolver->resolveForArticle('locked');

        $this->assertFalse($policy['enabled']);
    }
}
