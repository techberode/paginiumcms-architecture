<?php

declare(strict_types=1);

namespace PaginiumCMS\Modules\Newsletter\Services;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences;
use PaginiumCMS\Support\Lang;

final class NewsletterSubscribeService
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NewsletterRepositoryInterface $newsletterRepository,
        private NewsletterMailService $mailService,
        private Validator $validator
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array{
     *     ok: bool,
     *     status: int,
     *     payload?: array{id: string, created: bool, merged?: bool, pending?: bool},
     *     message?: string,
     *     errors?: array<string, list<string>>
     * }
     */
    public function subscribe(array $data, string $source): array
    {
        $honeypot = trim((string) ($data['_hp'] ?? ''));
        if ($honeypot !== '') {
            return [
                'ok' => true,
                'status' => 201,
                'payload' => [
                    'id' => 'hp_' . bin2hex(random_bytes(8)),
                    'created' => true,
                ],
                'message' => Lang::get('subscribed', [], 'newsletter'),
            ];
        }

        try {
            $validated = $this->validator->validate($data, [
                'email' => ['required', 'email', 'max:255'],
                'consent' => ['bool'],
            ]);
        } catch (ValidationException $e) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => Lang::get('validation_failed', [], 'newsletter'),
                'errors' => $e->getErrors(),
            ];
        }

        $newsletterSettings = $this->settings->group('newsletter');
        $enabledPreferences = NewsletterPreferences::parseEnabledList(
            (string) ($newsletterSettings['enabledPreferences'] ?? '')
        );
        $requireConsent = ($newsletterSettings['requireConsentCheckbox'] ?? false) === true;
        $requireDoubleOptIn = ($newsletterSettings['requireDoubleOptIn'] ?? false) === true;
        $confirmTokenTtlHours = max(1, (int) ($newsletterSettings['confirmTokenTtlHours'] ?? 72));

        if ($requireConsent && ($validated['consent'] ?? false) !== true) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => Lang::get('validation_failed', [], 'newsletter'),
                'errors' => [
                    'consent' => [Lang::get('consent_required', [], 'newsletter')],
                ],
            ];
        }

        /** @var list<mixed> $requestedPreferences */
        $requestedPreferences = is_array($data['preferences'] ?? null)
            ? $data['preferences']
            : [];
        $stringPreferences = array_values(array_filter(
            $requestedPreferences,
            static fn (mixed $value): bool => is_string($value)
        ));

        $preferences = NewsletterPreferences::normalizeSelection($stringPreferences, $enabledPreferences);
        if ($preferences === []) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => Lang::get('validation_failed', [], 'newsletter'),
                'errors' => [
                    'preferences' => [Lang::get('preferences_required', [], 'newsletter')],
                ],
            ];
        }

        if ($requireDoubleOptIn && !$this->mailService->isEmailConfigured()) {
            return [
                'ok' => false,
                'status' => 503,
                'message' => Lang::get('confirmation_email_unavailable', [], 'newsletter'),
            ];
        }

        $consentAt = $requireConsent ? date('c') : null;
        $result = $this->newsletterRepository->subscribe(
            (string) $validated['email'],
            $source,
            $preferences,
            $consentAt,
            $requireDoubleOptIn,
            $confirmTokenTtlHours
        );

        if ($requireDoubleOptIn && $result['pending'] && is_string($result['confirmToken'] ?? null)) {
            $sent = $this->mailService->sendConfirmationEmail(
                $result['email'],
                $result['confirmToken']
            );
            if (!$sent) {
                return [
                    'ok' => false,
                    'status' => 502,
                    'message' => Lang::get('confirmation_send_failed', [], 'newsletter'),
                ];
            }

            return [
                'ok' => true,
                'status' => $result['created'] ? 201 : 200,
                'payload' => [
                    'id' => $result['id'],
                    'created' => $result['created'],
                    'merged' => $result['merged'],
                    'pending' => true,
                ],
                'message' => Lang::get('confirmation_sent', [], 'newsletter'),
            ];
        }

        return [
            'ok' => true,
            'status' => $result['created'] ? 201 : 200,
            'payload' => [
                'id' => $result['id'],
                'created' => $result['created'],
                'merged' => $result['merged'],
                'pending' => false,
            ],
            'message' => Lang::get('subscribed', [], 'newsletter'),
        ];
    }
}
