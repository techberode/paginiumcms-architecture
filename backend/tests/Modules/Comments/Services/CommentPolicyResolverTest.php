<?php

declare(strict_types=1);

namespace PaginiumCMS\Tests\Modules\Comments\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Comments\Services\CommentPolicyResolver;
use PaginiumCMS\Modules\Comments\Services\CommentSpamHeuristicService;
use PaginiumCMS\Modules\Comments\Services\CommentSpamVerdict;
use PaginiumCMS\Modules\Comments\Services\CommentSubmissionVelocityStore;
use PaginiumCMS\Modules\Comments\Services\DisposableEmailDomainList;
use PHPUnit\Framework\TestCase;

class CommentPolicyResolverTest extends TestCase
{
    private function resolver(
        SettingsRepositoryInterface $settings,
        ContentRepositoryInterface $content,
    ): CommentPolicyResolver {
        DisposableEmailDomainList::resetCacheForTesting();

        $spam = new CommentSpamHeuristicService(
            $settings,
            new DisposableEmailDomainList(dirname(__DIR__, 4) . '/config/spam/disposable_email_domains.txt'),
            new CommentSubmissionVelocityStore(sys_get_temp_dir() . '/paginium_comment_velocity_resolver_' . uniqid('', true) . '.json')
        );

        return new CommentPolicyResolver($settings, $content, $spam);
    }

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

        $resolver = $this->resolver($settings, $content);
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

        $resolver = $this->resolver($settings, $content);
        $policy = $resolver->resolveForArticle('locked');

        $this->assertFalse($policy['enabled']);
    }
}
