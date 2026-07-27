<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use PaginiumCMS\Modules\Demo\Contracts\DemoDataProviderInterface;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;
use PaginiumCMS\Modules\Demo\Services\DemoMode;

/**
 * Poskytovateľ izolovaných MOCK dát. Aktívny len pri DEMO_MODE=true.
 */
final class DemoDataProvider implements DemoDataProviderInterface
{
    public function __construct(
        private DemoMode $demoMode
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->demoMode->isEnabled();
    }

    /**
     * @return array<int|string, mixed>
     */
    public function comments(?string $articleSlug = null): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $all = DemoFixtures::sampleComments();
        if ($articleSlug === null || $articleSlug === '') {
            return $all;
        }

        return array_values(array_filter(
            $all,
            static fn (array $c): bool => ($c['articleSlug'] ?? $c['article_slug'] ?? '') === $articleSlug
        ));
    }

    /**
     * @return array<int|string, mixed>
     */
    public function contactMessages(): array
    {
        return $this->isEnabled() ? DemoFixtures::sampleContactMessages() : [];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function newsletterSubscribers(): array
    {
        return $this->isEnabled() ? DemoFixtures::sampleNewsletterSubscribers() : [];
    }
}
