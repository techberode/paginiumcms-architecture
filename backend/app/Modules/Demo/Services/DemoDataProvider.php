<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Services;

use PaginiumCMS\Modules\Demo\Contracts\DemoDataProviderInterface;
use PaginiumCMS\Modules\Demo\Data\DemoFixtures;

/**
 * Poskytovateľ izolovaných MOCK dát. Aktívny len pri DEMO_MODE=true.
 */
final class DemoDataProvider implements DemoDataProviderInterface
{
    public function isEnabled(): bool
    {
        return filter_var(
            getenv('DEMO_MODE') ?: ($_ENV['DEMO_MODE'] ?? false),
            FILTER_VALIDATE_BOOLEAN
        );
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
            static fn (array $c): bool => ($c['article_slug'] ?? '') === $articleSlug
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
