<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Contracts;

interface NewsletterRepositoryInterface
{
    /**
     * @param list<string> $preferences
     * @return array{
     *     id: string,
     *     email: string,
     *     subscribedAt: string,
     *     source: string,
     *     preferences: list<string>,
     *     consentAt: ?string,
     *     status: string,
     *     created: bool,
     *     merged: bool,
     *     pending: bool,
     *     confirmToken: ?string
     * }
     */
    public function subscribe(
        string $email,
        string $source,
        array $preferences = [],
        ?string $consentAt = null,
        bool $requireConfirmation = false,
        int $confirmTokenTtlHours = 72
    ): array;

    /**
     * @return list<array{
     *     id: string,
     *     email: string,
     *     subscribedAt: string,
     *     source: string,
     *     preferences: list<string>,
     *     consentAt: ?string,
     *     status: string,
     *     unsubscribedAt: ?string
     * }>
     */
    public function findAll(): array;

    /**
     * @return array<string, int>
     */
    public function countBySource(): array;

    public function exportCsv(): string;

    /**
     * Active subscribers with a given preference key.
     *
     * @return list<array{
     *     id: string,
     *     email: string,
     *     subscribedAt: string,
     *     source: string,
     *     preferences: list<string>,
     *     consentAt: ?string,
     *     status: string,
     *     unsubscribedAt: ?string
     * }>
     */
    public function findActiveByPreference(string $preference): array;

    /**
     * @return array{ok: bool, reason?: string, email?: string}
     */
    public function confirmByToken(string $token): array;

    /**
     * @return array{ok: bool, reason?: string, email?: string}
     */
    public function unsubscribeByToken(string $token): array;
}
