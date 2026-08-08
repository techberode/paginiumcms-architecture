<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Content;

use PaginiumCMS\Core\I18n\Services\SupportedLocalesRegistry;
use PaginiumCMS\Core\Validation\ValidationException;

/**
 * Validates locale-scoped content writes (Iteration 73 Phase 2a).
 */
final class LocalizedContentValidator
{
    /** @var list<string> */
    private const VALID_STATUSES = ['draft', 'published', 'archived', 'scheduled'];

    public function __construct(
        private SupportedLocalesRegistry $locales,
    ) {
    }

    /**
     * @param array<string, mixed> $canonical
     * @throws ValidationException
     */
    public function validateLocaleStatusChange(array $canonical, string $locale, string $status): void
    {
        $errors = [];

        $locale = strtolower(trim($locale));
        if ($locale === '' || !$this->locales->isSupported($locale)) {
            $errors['locale'][] = 'Unsupported locale: ' . $locale;
        }

        if (!in_array($status, self::VALID_STATUSES, true)) {
            $errors['status'][] = 'Invalid locale status.';
        }

        if ($status === 'published') {
            /** @var array<string, mixed> $localizedContent */
            $localizedContent = is_array($canonical['localizedContent'] ?? null) ? $canonical['localizedContent'] : [];
            /** @var array<string, mixed> $slice */
            $slice = is_array($localizedContent[$locale] ?? null) ? $localizedContent[$locale] : [];
            if (trim((string) ($slice['title'] ?? '')) === '') {
                $errors['title'][] = 'Title is required when publishing a locale.';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors, 'Localized content validation failed', 422);
        }
    }

    /**
     * @param array<int|string, mixed> $data Write payload with `locale` key.
     *
     * @throws ValidationException
     */
    public function validateWritePayload(array $data): void
    {
        $errors = [];

        (new LocaleContentProposalPolicy())->assertDoesNotAutoPublish(
            $data,
            isset($data['proposalSource']) ? (string) $data['proposalSource'] : null
        );

        if (array_key_exists('localizedContent', $data) || array_key_exists('localeStatus', $data)) {
            $errors['locale'][] = 'Bulk localizedContent/localeStatus must not be sent directly; use locale-scoped fields.';
        }

        $locale = strtolower(trim((string) ($data['locale'] ?? '')));
        if ($locale === '') {
            $errors['locale'][] = 'Locale is required for locale-scoped writes.';
        } elseif (!$this->locales->isSupported($locale)) {
            $errors['locale'][] = 'Unsupported locale: ' . $locale;
        }

        $status = (string) ($data['status'] ?? 'draft');
        if (!in_array($status, self::VALID_STATUSES, true)) {
            $errors['status'][] = 'Invalid locale status.';
        }

        if ($status === 'published' && trim((string) ($data['title'] ?? '')) === '') {
            $errors['title'][] = 'Title is required when publishing a locale.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors, 'Localized content validation failed', 422);
        }
    }
}
