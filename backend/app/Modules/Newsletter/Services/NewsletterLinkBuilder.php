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

    public function unsubscribeUrl(string $token, ?string $preference = null): string
    {
        $url = $this->siteUrl() . '/newsletter/unsubscribe?token=' . rawurlencode($token);
        if ($preference !== null && $preference !== '') {
            $url .= '&preference=' . rawurlencode($preference);
        }

        return $url;
    }

    public function unsubscribeUrlForSubscriber(string $subscriberId, ?string $preference = null): string
    {
        return $this->unsubscribeUrl($this->unsubscribeToken->forSubscriber($subscriberId), $preference);
    }

    public function manageUrl(string $token): string
    {
        return $this->siteUrl() . '/newsletter/manage?token=' . rawurlencode($token);
    }

    public function manageUrlForSubscriber(string $subscriberId): string
    {
        return $this->manageUrl($this->unsubscribeToken->forSubscriber($subscriberId));
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
