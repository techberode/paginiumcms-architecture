<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\FlatFile\Contracts\ContentRepositoryInterface;
use PaginiumCMS\Core\FlatFile\Models\Article;
use PaginiumCMS\Core\Notification\NotificationService;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Support\Lang;

final class NewsletterMailService
{
    public function __construct(
        private NotificationService $notifications,
        private SettingsRepositoryInterface $settings,
        private NewsletterRepositoryInterface $subscribers,
        private ContentRepositoryInterface $content,
        private NewsletterSendStateStore $sendState,
        private NewsletterLinkBuilder $links
    ) {
    }

    public function isEmailConfigured(): bool
    {
        return in_array('email', $this->notifications->getAdapters(), true);
    }

    /**
     * @return array{
     *     configured: bool,
     *     sendEnabled: bool,
     *     weeklyDigestEnabled: bool,
     *     newArticleEnabled: bool,
     *     cmsReleaseEnabled: bool,
     *     lastWeeklyDigestAt: ?string
     * }
     */
    public function status(): array
    {
        $newsletter = $this->settings->group('newsletter');

        return [
            'configured' => $this->isEmailConfigured(),
            'sendEnabled' => ($newsletter['sendEnabled'] ?? false) === true,
            'weeklyDigestEnabled' => ($newsletter['weeklyDigestEnabled'] ?? false) === true,
            'newArticleEnabled' => ($newsletter['newArticleEnabled'] ?? false) === true,
            'cmsReleaseEnabled' => ($newsletter['cmsReleaseEnabled'] ?? false) === true,
            'lastWeeklyDigestAt' => $this->sendState->lastWeeklyDigestAt(),
        ];
    }

    /**
     * @return array{sent: int, failed: int, skipped: int, reason?: string}
     */
    public function sendWeeklyDigest(?\DateTimeImmutable $since = null): array
    {
        $newsletter = $this->settings->group('newsletter');
        if (($newsletter['sendEnabled'] ?? false) !== true) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'send_disabled'];
        }
        if (($newsletter['weeklyDigestEnabled'] ?? false) !== true) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'weekly_digest_disabled'];
        }
        if (!$this->isEmailConfigured()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'email_not_configured'];
        }

        $since ??= new \DateTimeImmutable('-7 days');
        $articles = $this->articlesPublishedSince($since);
        if ($articles === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'no_articles'];
        }

        $recipients = $this->subscribers->findActiveByPreference(NewsletterPreferences::WEEKLY_DIGEST);
        if ($recipients === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'no_subscribers'];
        }

        $siteName = $this->siteName();
        $subject = Lang::get('mail.weekly_digest_subject', ['site' => $siteName], 'newsletter');
        $html = $this->buildWeeklyDigestHtml($articles, $siteName);

        $result = $this->sendBatch($recipients, $subject, $html, (int) ($newsletter['sendBatchLimitPerRun'] ?? 50));
        if ($result['sent'] > 0) {
            $this->sendState->markWeeklyDigestSent();
        }

        return $result;
    }

    /**
     * @return array{sent: int, failed: int, skipped: int, reason?: string}
     */
    public function sendCmsRelease(string $version, string $title, string $body, ?string $url = null): array
    {
        $newsletter = $this->settings->group('newsletter');
        if (($newsletter['sendEnabled'] ?? false) !== true) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'send_disabled'];
        }
        if (($newsletter['cmsReleaseEnabled'] ?? false) !== true) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'cms_release_disabled'];
        }
        if (!$this->isEmailConfigured()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'email_not_configured'];
        }

        $version = trim($version);
        $title = trim($title);
        $body = trim($body);
        if ($version === '' || $title === '') {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'invalid_payload'];
        }

        $recipients = $this->subscribers->findActiveByPreference(NewsletterPreferences::CMS_RELEASE);
        if ($recipients === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'no_subscribers'];
        }

        $siteName = $this->siteName();
        $subject = Lang::get('mail.cms_release_subject', [
            'site' => $siteName,
            'version' => $version,
        ], 'newsletter');
        $html = $this->buildCmsReleaseHtml($version, $title, $body, $url, $siteName);

        return $this->sendBatch($recipients, $subject, $html, (int) ($newsletter['sendBatchLimitPerRun'] ?? 50));
    }

    /**
     * @return array{sent: int, failed: int, skipped: int, reason?: string}
     */
    public function sendNewArticleNotification(string $type, string $slug): array
    {
        if ($type !== 'article') {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'not_article'];
        }

        $newsletter = $this->settings->group('newsletter');
        if (($newsletter['sendEnabled'] ?? false) !== true) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'send_disabled'];
        }
        if (($newsletter['newArticleEnabled'] ?? false) !== true) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'new_article_disabled'];
        }
        if (!$this->isEmailConfigured()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'email_not_configured'];
        }

        $article = $this->content->findBySlug($slug, 'article');
        if (!$article instanceof Article || !$article->isPublished()) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'article_not_found'];
        }

        $cooldownHours = max(1, (int) ($newsletter['instantArticleCooldownHours'] ?? 24));
        $recipients = $this->subscribers->findActiveByPreference(NewsletterPreferences::NEW_ARTICLE);
        $eligible = [];
        foreach ($recipients as $recipient) {
            if (!$this->sendState->isArticleCooldownActive($recipient['email'], $cooldownHours)) {
                $eligible[] = $recipient;
            }
        }

        if ($eligible === []) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => 0, 'reason' => 'no_eligible_subscribers'];
        }

        $siteName = $this->siteName();
        $subject = Lang::get('mail.new_article_subject', [
            'site' => $siteName,
            'title' => $article->getTitle(),
        ], 'newsletter');
        $html = $this->buildNewArticleHtml($article, $siteName);

        $result = $this->sendBatch($eligible, $subject, $html, (int) ($newsletter['sendBatchLimitPerRun'] ?? 50));

        if ($result['sent'] > 0) {
            foreach ($eligible as $recipient) {
                $this->sendState->markArticleSent($recipient['email']);
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleContentStatusChange(array $context): void
    {
        if (($context['type'] ?? '') !== 'article') {
            return;
        }
        if (($context['status'] ?? '') !== 'published') {
            return;
        }
        if (($context['previousStatus'] ?? '') === 'published') {
            return;
        }

        $slug = (string) ($context['slug'] ?? '');
        if ($slug === '') {
            return;
        }

        $this->sendNewArticleNotification('article', $slug);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function handleScheduledPublish(array $context): void
    {
        if (($context['type'] ?? '') !== 'article') {
            return;
        }

        $slug = (string) ($context['slug'] ?? '');
        if ($slug === '') {
            return;
        }

        $this->sendNewArticleNotification('article', $slug);
    }

    public function sendTestEmail(string $to): bool
    {
        if (!$this->isEmailConfigured()) {
            return false;
        }

        $siteName = $this->siteName();
        $subject = Lang::get('mail.test_subject', ['site' => $siteName], 'newsletter');
        $html = '<p>' . htmlspecialchars(
            Lang::get('mail.test_body', ['site' => $siteName], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        ) . '</p>';

        return $this->sendOne($to, $subject, $html);
    }

    public function sendConfirmationEmail(string $to, string $confirmToken): bool
    {
        if (!$this->isEmailConfigured()) {
            return false;
        }

        $siteName = $this->siteName();
        $confirmUrl = $this->links->confirmUrl($confirmToken);
        $subject = Lang::get('mail.confirm_subject', ['site' => $siteName], 'newsletter');
        $intro = htmlspecialchars(
            Lang::get('mail.confirm_intro', ['site' => $siteName], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );
        $button = htmlspecialchars(
            Lang::get('mail.confirm_button', [], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );
        $safeUrl = htmlspecialchars($confirmUrl, ENT_QUOTES, 'UTF-8');
        $html = '<p>' . $intro . '</p>'
            . '<p><a href="' . $safeUrl . '"><strong>' . $button . '</strong></a></p>'
            . '<p style="font-size:12px;color:#666;">' . $safeUrl . '</p>';

        return $this->sendOne($to, $subject, $html);
    }

    /**
     * @param list<array{id?: string, email: string}> $recipients
     * @return array{sent: int, failed: int, skipped: int}
     */
    private function sendBatch(array $recipients, string $subject, string $html, int $limit): array
    {
        $sent = 0;
        $failed = 0;
        $skipped = max(0, count($recipients) - max(1, $limit));

        foreach (array_slice($recipients, 0, max(1, $limit)) as $recipient) {
            $email = $recipient['email'];
            if ($email === '') {
                continue;
            }

            $body = $this->appendUnsubscribeFooter($html, (string) ($recipient['id'] ?? ''));

            if ($this->sendOne($email, $subject, $body)) {
                ++$sent;
            } else {
                ++$failed;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }

    private function appendUnsubscribeFooter(string $html, string $subscriberId): string
    {
        if ($subscriberId === '') {
            return $html;
        }

        $manageUrl = htmlspecialchars(
            $this->links->manageUrlForSubscriber($subscriberId),
            ENT_QUOTES,
            'UTF-8'
        );
        $manageLabel = htmlspecialchars(
            Lang::get('mail.manage_link', [], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );
        $allUrl = htmlspecialchars(
            $this->links->unsubscribeUrlForSubscriber($subscriberId),
            ENT_QUOTES,
            'UTF-8'
        );
        $allLabel = htmlspecialchars(
            Lang::get('mail.unsubscribe_all_link', [], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );

        return $html
            . '<hr style="margin-top:24px;border:none;border-top:1px solid #ddd;">'
            . '<p style="font-size:12px;color:#666;">'
            . '<a href="' . $manageUrl . '">' . $manageLabel . '</a>'
            . ' · '
            . '<a href="' . $allUrl . '">' . $allLabel . '</a>'
            . '</p>';
    }

    private function sendOne(string $to, string $subject, string $html): bool
    {
        $replyTo = $this->replyTo();

        try {
            return $this->notifications->send('email', $to, $subject, $html, [
                'html' => $html,
                'reply_to' => $replyTo,
                'event' => 'newsletter',
            ]);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<Article>
     */
    private function articlesPublishedSince(\DateTimeImmutable $since): array
    {
        $articles = $this->content->findAllArticles(['status' => 'published']);
        $threshold = $since->getTimestamp();

        return array_values(array_filter(
            $articles,
            static function (Article $article) use ($threshold): bool {
                $date = $article->getDate();
                if ($date instanceof \DateTimeImmutable) {
                    return $date->getTimestamp() >= $threshold;
                }

                return $article->getModifiedAt() >= $threshold;
            }
        ));
    }

    /**
     * @param list<Article> $articles
     */
    private function buildWeeklyDigestHtml(array $articles, string $siteName): string
    {
        $intro = htmlspecialchars(
            Lang::get('mail.weekly_digest_intro', ['site' => $siteName], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );
        $items = [];
        foreach ($articles as $article) {
            $title = htmlspecialchars($article->getTitle(), ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars($this->articleUrl($article), ENT_QUOTES, 'UTF-8');
            $excerpt = htmlspecialchars($article->getExcerpt(180), ENT_QUOTES, 'UTF-8');
            $items[] = "<li><a href=\"{$url}\"><strong>{$title}</strong></a><br><span>{$excerpt}</span></li>";
        }

        return '<p>' . $intro . '</p><ul>' . implode('', $items) . '</ul>';
    }

    private function buildNewArticleHtml(Article $article, string $siteName): string
    {
        $title = htmlspecialchars($article->getTitle(), ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($this->articleUrl($article), ENT_QUOTES, 'UTF-8');
        $excerpt = htmlspecialchars($article->getExcerpt(240), ENT_QUOTES, 'UTF-8');
        $intro = htmlspecialchars(
            Lang::get('mail.new_article_intro', ['site' => $siteName], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );

        return '<p>' . $intro . '</p>'
            . '<p><a href="' . $url . '"><strong>' . $title . '</strong></a></p>'
            . '<p>' . $excerpt . '</p>';
    }

    private function buildCmsReleaseHtml(
        string $version,
        string $title,
        string $body,
        ?string $url,
        string $siteName
    ): string {
        $safeVersion = htmlspecialchars($version, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $intro = htmlspecialchars(
            Lang::get('mail.cms_release_intro', ['site' => $siteName, 'version' => $version], 'newsletter'),
            ENT_QUOTES,
            'UTF-8'
        );
        $safeBody = nl2br(htmlspecialchars($body, ENT_QUOTES, 'UTF-8'));
        $html = '<p>' . $intro . '</p>'
            . '<p><strong>' . $safeVersion . ' — ' . $safeTitle . '</strong></p>'
            . '<p>' . $safeBody . '</p>';

        $link = trim((string) $url);
        if ($link !== '') {
            $safeUrl = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
            $button = htmlspecialchars(Lang::get('mail.cms_release_button', [], 'newsletter'), ENT_QUOTES, 'UTF-8');
            $html .= '<p><a href="' . $safeUrl . '"><strong>' . $button . '</strong></a></p>';
        }

        return $html;
    }

    private function articleUrl(Article $article): string
    {
        $base = rtrim($this->siteUrl(), '/');

        return $base . '/blog/' . rawurlencode($article->getSlug());
    }

    private function siteName(): string
    {
        $general = $this->settings->group('general');

        return (string) ($general['siteName'] ?? 'PaginiumCMS');
    }

    private function siteUrl(): string
    {
        $general = $this->settings->group('general');
        $siteUrl = rtrim((string) ($general['siteUrl'] ?? ''), '/');
        if ($siteUrl !== '') {
            return $siteUrl;
        }

        $envUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');

        return is_string($envUrl) && $envUrl !== '' ? rtrim($envUrl, '/') : 'http://localhost:3025';
    }

    private function replyTo(): ?string
    {
        $newsletter = $this->settings->group('newsletter');
        $replyTo = trim((string) ($newsletter['replyTo'] ?? ''));

        return $replyTo !== '' ? $replyTo : null;
    }
}
