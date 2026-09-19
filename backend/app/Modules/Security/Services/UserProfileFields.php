<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Security\Services;

use PaginiumCMS\Core\Security\Services\OutboundUrlGuard;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Modules\Security\Models\User;
use PaginiumCMS\Support\LogSanitizer;

/**
 * Applies self-service / admin profile fields onto a User (It.93o).
 */
final class UserProfileFields
{
    public const MAX_NAME = 120;

    public const MAX_BIO = 500;

    public const MAX_JOB_TITLE = 80;

    public const MAX_PHONE = 40;

    /** @var list<string> */
    public const LOCALES = ['sk', 'en'];

    /**
     * @param array<string, mixed> $payload
     */
    public static function apply(User $user, array $payload): void
    {
        if (array_key_exists('name', $payload)) {
            $name = LogSanitizer::value(trim((string) $payload['name']), self::MAX_NAME);
            if (mb_strlen($name) < 2) {
                throw new ValidationException(['name' => ['Name must be at least 2 characters.']]);
            }
            $user->setName($name);
        }

        if (array_key_exists('bio', $payload)) {
            $user->setBio(LogSanitizer::value(trim((string) $payload['bio']), self::MAX_BIO));
        }

        if (array_key_exists('jobTitle', $payload)) {
            $user->setJobTitle(LogSanitizer::value(trim((string) $payload['jobTitle']), self::MAX_JOB_TITLE));
        }

        if (array_key_exists('phone', $payload)) {
            $user->setPhone(self::normalizePhone((string) $payload['phone']));
        }

        if (array_key_exists('timezone', $payload)) {
            $user->setTimezone(self::normalizeTimezone((string) $payload['timezone']));
        }

        if (array_key_exists('locale', $payload)) {
            $user->setLocale(self::normalizeLocale((string) $payload['locale']));
        }

        if (array_key_exists('notifyFailedLogin', $payload)) {
            $user->setNotifyFailedLogin(self::toBool($payload['notifyFailedLogin']));
        }

        if (array_key_exists('notifySecurityIncident', $payload)) {
            $user->setNotifySecurityIncident(self::toBool($payload['notifySecurityIncident']));
        }

        if (array_key_exists('address', $payload)) {
            $user->setAddress(self::normalizeAddress($payload['address']));
        }

        if (array_key_exists('experience', $payload)) {
            $user->setExperience(self::normalizeExperience($payload['experience']));
        }

        if (array_key_exists('education', $payload)) {
            $user->setEducation(self::normalizeEducation($payload['education']));
        }

        if (array_key_exists('socialAccounts', $payload)) {
            $user->setSocialAccounts(
                self::finalizeSocialAccounts(
                    self::normalizeSocialAccounts($payload['socialAccounts']),
                    is_array($payload['socialAccounts']) ? array_values($payload['socialAccounts']) : [],
                    $user->getSocialAccounts()
                )
            );
        }

        if (array_key_exists('publish', $payload)) {
            $publish = self::normalizePublish($payload['publish']);
            self::assertPublishSocialsVerified($user, $publish);
            $user->setPublish($publish);
        }

        if (array_key_exists('chatEnabled', $payload)) {
            $user->setChatEnabled(self::toBool($payload['chatEnabled']));
        }

        if (array_key_exists('deskMailEnabled', $payload)) {
            $user->setDeskMailEnabled(self::toBool($payload['deskMailEnabled']));
        }

        if (array_key_exists('deskBubbleEnabled', $payload)) {
            $user->setDeskBubbleEnabled(self::toBool($payload['deskBubbleEnabled']));
        }

        if (array_key_exists('deskBubbleAnchor', $payload)) {
            $user->setDeskBubbleAnchor((string) $payload['deskBubbleAnchor']);
        }

        if (array_key_exists('deskBubbleX', $payload)) {
            $user->setDeskBubbleX((int) $payload['deskBubbleX']);
        }

        if (array_key_exists('deskBubbleY', $payload)) {
            $user->setDeskBubbleY((int) $payload['deskBubbleY']);
        }
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = LogSanitizer::value(trim($phone), self::MAX_PHONE);
        if ($phone === '') {
            return '';
        }

        if (preg_match('/^[0-9+().\s-]{1,40}$/', $phone) !== 1) {
            throw new ValidationException(['phone' => ['Invalid phone number.']]);
        }

        return $phone;
    }

    public static function normalizeTimezone(string $timezone): string
    {
        $timezone = trim($timezone);
        if ($timezone === '') {
            return '';
        }

        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new ValidationException(['timezone' => ['Invalid timezone.']]);
        }

        return $timezone;
    }

    public static function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        if ($locale === '') {
            return '';
        }

        if (!in_array($locale, self::LOCALES, true)) {
            throw new ValidationException(['locale' => ['Unsupported locale.']]);
        }

        return $locale;
    }

    public const MAX_ADDRESS = 120;

    public const MAX_CV_ITEMS = 12;

    public const MAX_SOCIALS = 12;

    /** @var list<string> */
    public const SOCIAL_PLATFORMS = [
        'github',
        'gitlab',
        'twitter',
        'facebook',
        'instagram',
        'linkedin',
        'youtube',
        'mastodon',
        'discord',
        'telegram',
        'whatsapp',
        'messenger',
        'website',
        'email',
    ];

    /**
     * @return array{street: string, city: string, postal: string, country: string}
     */
    public static function emptyAddress(): array
    {
        return ['street' => '', 'city' => '', 'postal' => '', 'country' => ''];
    }

    /**
     * @return array{address: bool, experience: bool, education: bool, phone: bool, email: bool, socials: bool, contact: bool, support: bool}
     */
    public static function emptyPublish(): array
    {
        return [
            'address' => false,
            'experience' => false,
            'education' => false,
            'phone' => false,
            'email' => false,
            'socials' => false,
            'contact' => false,
            'support' => false,
        ];
    }

    /**
     * @return array{street: string, city: string, postal: string, country: string}
     */
    public static function normalizeAddress(mixed $raw): array
    {
        $row = is_array($raw) ? $raw : [];

        return [
            'street' => LogSanitizer::value(trim((string) ($row['street'] ?? '')), self::MAX_ADDRESS),
            'city' => LogSanitizer::value(trim((string) ($row['city'] ?? '')), 80),
            'postal' => LogSanitizer::value(trim((string) ($row['postal'] ?? '')), 20),
            'country' => LogSanitizer::value(trim((string) ($row['country'] ?? '')), 80),
        ];
    }

    /**
     * @return list<array{id: string, org: string, role: string, years: string}>
     */
    public static function normalizeExperience(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach (array_slice(array_values($raw), 0, self::MAX_CV_ITEMS) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $org = LogSanitizer::value(trim((string) ($row['org'] ?? '')), 120);
            $role = LogSanitizer::value(trim((string) ($row['role'] ?? '')), 120);
            $years = LogSanitizer::value(trim((string) ($row['years'] ?? '')), 40);
            if ($org === '' && $role === '') {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            $items[] = [
                'id' => $id !== '' ? LogSanitizer::value($id, 40) : 'exp-' . ($index + 1),
                'org' => $org,
                'role' => $role,
                'years' => $years,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, school: string, field: string, years: string}>
     */
    public static function normalizeEducation(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach (array_slice(array_values($raw), 0, self::MAX_CV_ITEMS) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $school = LogSanitizer::value(trim((string) ($row['school'] ?? '')), 120);
            $field = LogSanitizer::value(trim((string) ($row['field'] ?? '')), 120);
            $years = LogSanitizer::value(trim((string) ($row['years'] ?? '')), 40);
            if ($school === '' && $field === '') {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            $items[] = [
                'id' => $id !== '' ? LogSanitizer::value($id, 40) : 'edu-' . ($index + 1),
                'school' => $school,
                'field' => $field,
                'years' => $years,
            ];
        }

        return $items;
    }

    /**
     * @return list<array{id: string, platform: string, url: string, label: string, directChat: bool, notify: bool, verifiedAt: int}>
     */
    public static function normalizeSocialAccounts(mixed $raw, bool $fromStorage = false): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach (array_slice(array_values($raw), 0, self::MAX_SOCIALS) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            $platform = strtolower(trim((string) ($row['platform'] ?? 'website')));
            if (!in_array($platform, self::SOCIAL_PLATFORMS, true)) {
                $platform = 'website';
            }
            $url = SocialAccountLinkProbe::normalizeStorageUrl($platform, (string) ($row['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $label = LogSanitizer::value(trim((string) ($row['label'] ?? '')), 80);
            $items[] = [
                'id' => $id !== '' ? LogSanitizer::value($id, 40) : $platform . '-' . ($index + 1),
                'platform' => $platform,
                'url' => LogSanitizer::value($url, 500),
                'label' => $label !== '' ? $label : ucfirst($platform),
                'directChat' => self::toBool($row['directChat'] ?? false),
                'notify' => self::toBool($row['notify'] ?? false),
                // Client payloads must not self-attest; stored JSON may keep a prior probe stamp.
                'verifiedAt' => $fromStorage ? max(0, (int) ($row['verifiedAt'] ?? 0)) : 0,
            ];
        }

        return $items;
    }

    /**
     * @param list<array{id: string, platform: string, url: string, label: string, directChat: bool, notify: bool, verifiedAt: int}> $normalized
     * @param list<mixed> $rawRows
     * @param list<array<string, mixed>> $previous
     *
     * @return list<array{id: string, platform: string, url: string, label: string, directChat: bool, notify: bool, verifiedAt: int}>
     */
    public static function finalizeSocialAccounts(array $normalized, array $rawRows, array $previous): array
    {
        $probe = self::socialProbe();
        $rawById = [];
        foreach ($rawRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            if ($id !== '') {
                $rawById[$id] = $row;
            }
        }

        $previousById = [];
        foreach ($previous as $row) {
            $id = trim((string) ($row['id'] ?? ''));
            if ($id !== '') {
                $previousById[$id] = $row;
            }
        }

        $final = [];
        foreach ($normalized as $account) {
            $id = $account['id'];
            $raw = $rawById[$id] ?? null;
            $prev = $previousById[$id] ?? null;
            $clientRequestedVerify = is_array($raw) && (int) ($raw['verifiedAt'] ?? 0) > 0;

            $unchangedVerified = is_array($prev)
                && SocialAccountLinkProbe::isVerified($prev)
                && ($prev['platform'] ?? '') === $account['platform']
                && ($prev['url'] ?? '') === $account['url'];

            $url = $account['url'];
            $verifiedAt = 0;
            if ($unchangedVerified) {
                $verifiedAt = (int) ($prev['verifiedAt'] ?? 0);
            } elseif ($clientRequestedVerify) {
                $result = $probe->verify($account['platform'], $account['url']);
                if ($result['ok']) {
                    $url = LogSanitizer::value($result['normalizedUrl'], 500);
                    $verifiedAt = time();
                }
            }

            $final[] = [
                'id' => $account['id'],
                'platform' => $account['platform'],
                'url' => $url,
                'label' => $account['label'],
                'directChat' => $account['directChat'],
                'notify' => $account['notify'],
                'verifiedAt' => $verifiedAt,
            ];
        }

        return $final;
    }

    /**
     * @param array{address: bool, experience: bool, education: bool, phone: bool, email: bool, socials: bool, contact: bool, support: bool} $publish
     */
    public static function assertPublishSocialsVerified(User $user, array $publish): void
    {
        if ($publish['socials'] !== true) {
            return;
        }

        foreach ($user->getSocialAccounts() as $account) {
            if (!SocialAccountLinkProbe::isVerified($account)) {
                throw new ValidationException([
                    'socialAccounts' => ['Publish social accounts requires each link to be verified first.'],
                    'publish.socials' => ['Verify every social link before publishing.'],
                ]);
            }
        }
    }

    private static function socialProbe(): SocialAccountLinkProbe
    {
        /** @var SocialAccountLinkProbe|null $probe */
        static $probe = null;
        $probe ??= new SocialAccountLinkProbe(OutboundUrlGuard::fromEnv());

        return $probe;
    }

    /**
     * @return array{address: bool, experience: bool, education: bool, phone: bool, email: bool, socials: bool, contact: bool, support: bool}
     */
    public static function normalizePublish(mixed $raw): array
    {
        $row = is_array($raw) ? $raw : [];
        $empty = self::emptyPublish();
        foreach (array_keys($empty) as $key) {
            if (array_key_exists($key, $row)) {
                $empty[$key] = self::toBool($row[$key]);
            }
        }

        return $empty;
    }

    public static function chatUrl(string $platform, string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        return match ($platform) {
            'telegram' => self::telegramUrl($url),
            'whatsapp' => self::whatsappUrl($url),
            'messenger' => self::messengerUrl($url),
            'email' => str_starts_with(strtolower($url), 'mailto:') ? $url : 'mailto:' . $url,
            default => filter_var($url, FILTER_VALIDATE_URL) ? $url : '',
        };
    }

    private static function telegramUrl(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $handle = ltrim($url, '@');
        $handle = preg_replace('/[^a-zA-Z0-9_]/', '', $handle) ?? '';

        return $handle !== '' ? 'https://t.me/' . $handle : '';
    }

    private static function whatsappUrl(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $digits = preg_replace('/\D+/', '', $url) ?? '';

        return $digits !== '' ? 'https://wa.me/' . $digits : '';
    }

    private static function messengerUrl(string $url): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $handle = preg_replace('/[^a-zA-Z0-9._-]/', '', ltrim($url, '@')) ?? '';

        return $handle !== '' ? 'https://m.me/' . $handle : '';
    }

    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
