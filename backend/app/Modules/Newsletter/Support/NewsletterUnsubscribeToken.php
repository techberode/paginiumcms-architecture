<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Support;

final class NewsletterUnsubscribeToken
{
    public function __construct(private ?string $appKey)
    {
    }

    public function forSubscriber(string $subscriberId): string
    {
        $key = trim((string) ($this->appKey ?? ''));
        if ($key === '') {
            return hash('sha256', 'newsletter-unsub-fallback:' . $subscriberId);
        }

        return hash_hmac('sha256', 'newsletter-unsub:' . $subscriberId, $key);
    }

    public function matches(string $subscriberId, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        return hash_equals($this->forSubscriber($subscriberId), $token);
    }
}
