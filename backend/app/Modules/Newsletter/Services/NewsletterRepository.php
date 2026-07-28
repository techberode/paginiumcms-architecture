<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\FlatFile\Contracts\FileReaderInterface;
use PaginiumCMS\Core\FlatFile\Contracts\FileWriterInterface;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterTokenSupport;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterUnsubscribeToken;
use PaginiumCMS\Support\LogSanitizer;
use RuntimeException;

final class NewsletterRepository implements NewsletterRepositoryInterface
{
    private const FILE = 'data/newsletter/subscribers.json';

    public function __construct(
        private FileReaderInterface $reader,
        private FileWriterInterface $writer,
        private NewsletterUnsubscribeToken $unsubscribeToken
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function subscribe(
        string $email,
        string $source,
        array $preferences = [],
        ?string $consentAt = null,
        bool $requireConfirmation = false,
        int $confirmTokenTtlHours = 72
    ): array {
        $normalizedEmail = strtolower(trim($email));
        $normalizedSource = trim($source) !== '' ? trim($source) : 'maintenance';
        $normalizedPreferences = NewsletterPreferences::normalizeSelection(
            $preferences,
            NewsletterPreferences::ALL
        );

        return $this->withLockedStore(function (array &$store) use (
            $normalizedEmail,
            $normalizedSource,
            $normalizedPreferences,
            $consentAt,
            $requireConfirmation,
            $confirmTokenTtlHours
        ): array {
            foreach ($store as $index => $entry) {
                if (strtolower((string) ($entry['email'] ?? '')) !== $normalizedEmail) {
                    continue;
                }

                return $this->updateExistingSubscriber(
                    $store,
                    $index,
                    $entry,
                    $normalizedEmail,
                    $normalizedSource,
                    $normalizedPreferences,
                    $consentAt,
                    $requireConfirmation,
                    $confirmTokenTtlHours
                );
            }

            return $this->createSubscriber(
                $store,
                $normalizedEmail,
                $normalizedSource,
                $normalizedPreferences,
                $consentAt,
                $requireConfirmation,
                $confirmTokenTtlHours
            );
        });
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-impure
     */
    public function findAll(): array
    {
        $store = $this->readStore();

        $entries = [];
        foreach ($store as $entry) {
            $entries[] = $this->normalizeEntry($entry);
        }

        usort($entries, static fn (array $a, array $b): int => strcmp($b['subscribedAt'], $a['subscribedAt']));

        return $entries;
    }

    /**
     * {@inheritDoc}
     */
    public function countBySource(): array
    {
        $counts = [];
        foreach ($this->findAll() as $entry) {
            $source = $entry['source'];
            $counts[$source] = ($counts[$source] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /**
     * {@inheritDoc}
     */
    public function exportCsv(): string
    {
        $entries = $this->findAll();
        $lines = ['id,email,subscribed_at,source,preferences,status,consent_at,unsubscribed_at'];

        foreach ($entries as $entry) {
            $lines[] = implode(',', array_map(
                static fn (string $value): string => '"' . str_replace('"', '""', LogSanitizer::value($value)) . '"',
                [
                    $entry['id'],
                    $entry['email'],
                    $entry['subscribedAt'],
                    $entry['source'],
                    implode('|', $entry['preferences']),
                    $entry['status'],
                    $entry['consentAt'] ?? '',
                    $entry['unsubscribedAt'] ?? '',
                ]
            ));
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * {@inheritDoc}
     *
     * @phpstan-impure
     */
    public function findActiveByPreference(string $preference): array
    {
        $key = trim($preference);
        if ($key === '') {
            return [];
        }

        return array_values(array_filter(
            $this->findAll(),
            static fn (array $entry): bool => $entry['status'] === 'active'
                && in_array($key, $entry['preferences'], true)
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function confirmByToken(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'reason' => 'invalid_token'];
        }

        return $this->withLockedStore(function (array &$store) use ($token): array {
            $now = time();
            foreach ($store as $index => $entry) {
                $hash = (string) ($entry['confirmTokenHash'] ?? '');
                $expires = (int) ($entry['confirmTokenExpires'] ?? 0);
                if ($hash === '' || !NewsletterTokenSupport::verify($token, $hash)) {
                    continue;
                }

                if ($expires > 0 && $expires < $now) {
                    return ['ok' => false, 'reason' => 'expired_token'];
                }

                $store[$index]['status'] = 'active';
                unset($store[$index]['confirmTokenHash'], $store[$index]['confirmTokenExpires']);
                unset($store[$index]['unsubscribedAt']);

                return [
                    'ok' => true,
                    'email' => (string) ($entry['email'] ?? ''),
                ];
            }

            return ['ok' => false, 'reason' => 'invalid_token'];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function unsubscribeByToken(string $token, ?string $preference = null): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'reason' => 'invalid_token'];
        }

        $preferenceKey = $preference !== null ? trim($preference) : '';
        if ($preferenceKey !== '' && !in_array($preferenceKey, NewsletterPreferences::ALL, true)) {
            return ['ok' => false, 'reason' => 'invalid_preference'];
        }

        return $this->withLockedStore(function (array &$store) use ($token, $preferenceKey): array {
            $index = $this->findIndexByManageToken($store, $token);
            if ($index === null) {
                return ['ok' => false, 'reason' => 'invalid_token'];
            }

            $entry = $store[$index];
            $email = (string) ($entry['email'] ?? '');

            if (($entry['status'] ?? '') === 'unsubscribed') {
                return [
                    'ok' => true,
                    'email' => $email,
                    'reason' => 'already_unsubscribed',
                    'fullyUnsubscribed' => true,
                ];
            }

            if ($preferenceKey === '') {
                $store[$index]['status'] = 'unsubscribed';
                $store[$index]['unsubscribedAt'] = date('c');
                unset($store[$index]['confirmTokenHash'], $store[$index]['confirmTokenExpires']);

                return [
                    'ok' => true,
                    'email' => $email,
                    'fullyUnsubscribed' => true,
                ];
            }

            $preferences = $this->normalizeStoredPreferences($entry);
            $remaining = array_values(array_filter(
                $preferences,
                static fn (string $key): bool => $key !== $preferenceKey
            ));

            if ($remaining === []) {
                $store[$index]['status'] = 'unsubscribed';
                $store[$index]['unsubscribedAt'] = date('c');
                unset($store[$index]['confirmTokenHash'], $store[$index]['confirmTokenExpires']);

                return [
                    'ok' => true,
                    'email' => $email,
                    'preference' => $preferenceKey,
                    'fullyUnsubscribed' => true,
                ];
            }

            $store[$index]['preferences'] = $remaining;
            $store[$index]['status'] = 'active';
            unset($store[$index]['unsubscribedAt']);

            return [
                'ok' => true,
                'email' => $email,
                'preference' => $preferenceKey,
                'fullyUnsubscribed' => false,
            ];
        });
    }

    /**
     * {@inheritDoc}
     */
    public function findByManageToken(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'reason' => 'invalid_token'];
        }

        foreach ($this->readStore() as $entry) {
            $id = (string) ($entry['id'] ?? '');
            if ($id === '' || !$this->unsubscribeToken->matches($id, $token)) {
                continue;
            }

            $normalized = $this->normalizeEntry($entry);

            return [
                'ok' => true,
                'id' => $normalized['id'],
                'email' => $normalized['email'],
                'preferences' => $normalized['preferences'],
                'status' => $normalized['status'],
            ];
        }

        return ['ok' => false, 'reason' => 'invalid_token'];
    }

    /**
     * {@inheritDoc}
     */
    public function updatePreferencesByToken(string $token, array $preferences): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['ok' => false, 'reason' => 'invalid_token'];
        }

        $normalizedPreferences = NewsletterPreferences::normalizeSelection(
            $preferences,
            NewsletterPreferences::ALL
        );
        if ($normalizedPreferences === []) {
            return ['ok' => false, 'reason' => 'preferences_required'];
        }

        return $this->withLockedStore(function (array &$store) use ($token, $normalizedPreferences): array {
            $index = $this->findIndexByManageToken($store, $token);
            if ($index === null) {
                return ['ok' => false, 'reason' => 'invalid_token'];
            }

            $entry = $store[$index];
            if (($entry['status'] ?? '') === 'unsubscribed') {
                return ['ok' => false, 'reason' => 'unsubscribed'];
            }

            $store[$index]['preferences'] = $normalizedPreferences;
            if (($entry['status'] ?? '') === 'pending') {
                // Keep pending until confirmed.
            } else {
                $store[$index]['status'] = 'active';
            }
            unset($store[$index]['unsubscribedAt']);

            return [
                'ok' => true,
                'email' => (string) ($entry['email'] ?? ''),
                'preferences' => $normalizedPreferences,
                'status' => (string) ($store[$index]['status'] ?? 'active'),
            ];
        });
    }

    /**
     * @param array<int, array<string, mixed>> $store
     */
    private function findIndexByManageToken(array $store, string $token): ?int
    {
        foreach ($store as $index => $entry) {
            $id = (string) ($entry['id'] ?? '');
            if ($id !== '' && $this->unsubscribeToken->matches($id, $token)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $store
     * @param array<string, mixed> $entry
     * @param list<string> $normalizedPreferences
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
    private function updateExistingSubscriber(
        array &$store,
        int $index,
        array $entry,
        string $normalizedEmail,
        string $normalizedSource,
        array $normalizedPreferences,
        ?string $consentAt,
        bool $requireConfirmation,
        int $confirmTokenTtlHours
    ): array {
        $existingPreferences = $this->normalizeStoredPreferences($entry);
        $mergedPreferences = NewsletterPreferences::merge($existingPreferences, $normalizedPreferences);
        $merged = $mergedPreferences !== $existingPreferences;
        $currentStatus = (string) ($entry['status'] ?? 'active');

        if ($merged) {
            $store[$index]['preferences'] = $mergedPreferences;
        }

        if ($consentAt !== null) {
            $store[$index]['consentAt'] = $consentAt;
        }

        $confirmToken = null;
        $pending = false;

        if ($requireConfirmation && $currentStatus !== 'active') {
            $confirmToken = $this->applyPendingConfirmation($store[$index], $confirmTokenTtlHours);
            $pending = true;
        } else {
            $store[$index]['status'] = 'active';
            unset($store[$index]['confirmTokenHash'], $store[$index]['confirmTokenExpires'], $store[$index]['unsubscribedAt']);
        }

        return [
            'id' => (string) ($entry['id'] ?? ''),
            'email' => $normalizedEmail,
            'subscribedAt' => (string) ($entry['subscribedAt'] ?? date('c')),
            'source' => (string) ($entry['source'] ?? $normalizedSource),
            'preferences' => $mergedPreferences,
            'consentAt' => isset($store[$index]['consentAt'])
                ? (string) $store[$index]['consentAt']
                : null,
            'status' => (string) ($store[$index]['status'] ?? 'active'),
            'created' => false,
            'merged' => $merged,
            'pending' => $pending,
            'confirmToken' => $confirmToken,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $store
     * @param list<string> $normalizedPreferences
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
    private function createSubscriber(
        array &$store,
        string $normalizedEmail,
        string $normalizedSource,
        array $normalizedPreferences,
        ?string $consentAt,
        bool $requireConfirmation,
        int $confirmTokenTtlHours
    ): array {
        $record = [
            'id' => uniqid('nl_', true),
            'email' => $normalizedEmail,
            'subscribedAt' => date('c'),
            'source' => $normalizedSource,
            'preferences' => $normalizedPreferences,
            'status' => $requireConfirmation ? 'pending' : 'active',
        ];

        if ($consentAt !== null) {
            $record['consentAt'] = $consentAt;
        }

        $confirmToken = null;
        if ($requireConfirmation) {
            $confirmToken = $this->applyPendingConfirmation($record, $confirmTokenTtlHours);
        }

        $store[] = $record;

        return [
            'id' => $record['id'],
            'email' => $record['email'],
            'subscribedAt' => $record['subscribedAt'],
            'source' => $record['source'],
            'preferences' => $normalizedPreferences,
            'consentAt' => $consentAt,
            'status' => (string) $record['status'],
            'created' => true,
            'merged' => false,
            'pending' => $requireConfirmation,
            'confirmToken' => $confirmToken,
        ];
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function applyPendingConfirmation(array &$entry, int $confirmTokenTtlHours): string
    {
        $token = NewsletterTokenSupport::generate();
        $entry['status'] = 'pending';
        $entry['confirmTokenHash'] = NewsletterTokenSupport::hash($token);
        $entry['confirmTokenExpires'] = time() + max(1, $confirmTokenTtlHours) * 3600;
        unset($entry['unsubscribedAt']);

        return $token;
    }

    /**
     * @param array<string, mixed> $entry
     * @return array{
     *     id: string,
     *     email: string,
     *     subscribedAt: string,
     *     source: string,
     *     preferences: list<string>,
     *     consentAt: ?string,
     *     status: string,
     *     unsubscribedAt: ?string
     * }
     */
    private function normalizeEntry(array $entry): array
    {
        $consentAt = $entry['consentAt'] ?? null;
        $unsubscribedAt = $entry['unsubscribedAt'] ?? null;

        return [
            'id' => (string) ($entry['id'] ?? ''),
            'email' => (string) ($entry['email'] ?? ''),
            'subscribedAt' => (string) ($entry['subscribedAt'] ?? ''),
            'source' => (string) ($entry['source'] ?? ''),
            'preferences' => $this->normalizeStoredPreferences($entry),
            'consentAt' => is_string($consentAt) && $consentAt !== '' ? $consentAt : null,
            'status' => (string) ($entry['status'] ?? 'active'),
            'unsubscribedAt' => is_string($unsubscribedAt) && $unsubscribedAt !== '' ? $unsubscribedAt : null,
        ];
    }

    /**
     * @param array<string, mixed> $entry
     * @return list<string>
     */
    private function normalizeStoredPreferences(array $entry): array
    {
        $raw = $entry['preferences'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        return NewsletterPreferences::normalizeSelection(
            array_values(array_filter($raw, static fn (mixed $value): bool => is_string($value))),
            NewsletterPreferences::ALL
        );
    }

    /**
     * @template T
     * @param callable(array<int, array<string, mixed>>): T $mutator
     * @return T
     */
    private function withLockedStore(callable $mutator): mixed
    {
        $absolutePath = rtrim($this->reader->getBasePath(), '/') . '/' . ltrim(self::FILE, '/');
        $dir = dirname($absolutePath);
        if (!is_dir($dir)) {
            $this->writer->createDirectory(dirname(self::FILE));
        }

        $handle = fopen($absolutePath, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Nepodarilo sa otvoriť súbor newsletteru: ' . $absolutePath);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Nepodarilo sa získať zámok newsletteru.');
            }

            $store = $this->readHandle($handle);
            $before = json_encode($store);
            $result = $mutator($store);
            $after = json_encode($store);

            if ($after !== $before) {
                $this->writeHandle($handle, $store);
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readStore(): array
    {
        if (!$this->reader->exists(self::FILE)) {
            return [];
        }

        try {
            $decoded = json_decode($this->reader->read(self::FILE), true);
        } catch (\Throwable) {
            return [];
        }

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param resource $handle
     * @return list<array<string, mixed>>
     */
    private function readHandle($handle): array
    {
        rewind($handle);
        $raw = stream_get_contents($handle);
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @param resource $handle
     * @param list<array<string, mixed>> $store
     */
    private function writeHandle($handle, array $store): void
    {
        $payload = json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = '[]';
        }

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, $payload);
        fflush($handle);
    }
}
