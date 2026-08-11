<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Contracts;

use PaginiumCMS\Http\Support\BulkBatchResult;

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
     * @return array{
     *     id: string,
     *     email: string,
     *     subscribedAt: string,
     *     source: string,
     *     preferences: list<string>,
     *     consentAt: ?string,
     *     status: string,
     *     unsubscribedAt: ?string
     * }|null
     */
    public function findByEmail(string $email): ?array;

    /**
     * Replaces subscriber email with a pseudonym address (Irreversible for the original email).
     */
    public function anonymizeEmail(string $email, string $pseudonymEmail): bool;

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
     * @return array{ok: bool, reason?: string, email?: string, preference?: string, fullyUnsubscribed?: bool}
     */
    public function unsubscribeByToken(string $token, ?string $preference = null): array;

    /**
     * @return array{
     *     ok: bool,
     *     reason?: string,
     *     id?: string,
     *     email?: string,
     *     preferences?: list<string>,
     *     status?: string
     * }
     */
    public function findByManageToken(string $token): array;

    /**
     * @param list<string> $preferences
     * @return array{
     *     ok: bool,
     *     reason?: string,
     *     email?: string,
     *     preferences?: list<string>,
     *     status?: string
     * }
     */
    public function updatePreferencesByToken(string $token, array $preferences): array;

    /**
     * @return array{ok: bool, reason?: string, email?: string}
     */
    public function unsubscribeById(string $id): array;

    public function deleteById(string $id): bool;

    /**
     * @param list<string> $ids
     */
    public function bulkUnsubscribe(array $ids): BulkBatchResult;

    /**
     * @param list<string> $ids
     */
    public function bulkDelete(array $ids): BulkBatchResult;
}
