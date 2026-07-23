<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Contracts;

interface NewsletterRepositoryInterface
{
    /**
     * @return array{id: string, email: string, subscribedAt: string, source: string, created: bool}
     */
    public function subscribe(string $email, string $source): array;

    /**
     * @return list<array{id: string, email: string, subscribedAt: string, source: string}>
     */
    public function findAll(): array;
}
