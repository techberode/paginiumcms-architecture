<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Demo\Contracts;

/**
 * Read-only prístup k MOCK dátam Demo modulu.
 */
interface DemoDataProviderInterface
{
    public function isEnabled(): bool;

    /**
     * @return list<array<string, mixed>>
     */
    public function comments(?string $articleSlug = null): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function contactMessages(): array;

    /**
     * @return list<array<string, mixed>>
     */
    public function newsletterSubscribers(): array;
}
