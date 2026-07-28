<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;

final class NewsletterLinkBuilder
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NewsletterUnsubscribeToken $unsubscribeToken
    ) {
    }

    public function confirmUrl(string $token): string
    {
        return $this->siteUrl() . '/newsletter/confirm?token=' . rawurlencode($token);
    }

    public function unsubscribeUrl(string $token): string
    {
        return $this->siteUrl() . '/newsletter/unsubscribe?token=' . rawurlencode($token);
    }

    public function unsubscribeUrlForSubscriber(string $subscriberId): string
    {
        return $this->unsubscribeUrl($this->unsubscribeToken->forSubscriber($subscriberId));
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
}
