<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Newsletter;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterSubscribeService;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NewsletterController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NewsletterSubscribeService $subscribeService,
        private NewsletterRepositoryInterface $newsletterRepository,
        private JsonResponder $json
    ) {
    }

    public function subscribe(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $newsletter = $this->settings->group('newsletter');
        if (($newsletter['footerEnabled'] ?? false) !== true) {
            return $this->json->error($response, Lang::get('disabled', [], 'newsletter'), 403);
        }

        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'newsletter'), 400);
        }

        $result = $this->subscribeService->subscribe($data, 'footer');
        if (!$result['ok']) {
            if (isset($result['errors'])) {
                return $this->json->validation(
                    $response,
                    $result['message'] ?? Lang::get('validation_failed', [], 'newsletter'),
                    $result['errors']
                );
            }

            return $this->json->error(
                $response,
                $result['message'] ?? Lang::get('invalid_payload', [], 'newsletter'),
                $result['status']
            );
        }

        return $this->json->success(
            $response,
            $result['payload'] ?? [],
            $result['status'],
            $result['message'] ?? Lang::get('subscribed', [], 'newsletter')
        );
    }

    public function confirm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $token = trim((string) ($params['token'] ?? ''));

        if ($token === '') {
            return $this->json->error($response, Lang::get('token_required', [], 'newsletter'), 400);
        }

        $result = $this->newsletterRepository->confirmByToken($token);
        if (!$result['ok']) {
            $message = match ($result['reason'] ?? '') {
                'expired_token' => Lang::get('confirm_expired', [], 'newsletter'),
                default => Lang::get('confirm_invalid', [], 'newsletter'),
            };

            return $this->json->respond($response, [
                'success' => false,
                'message' => $message,
                'reason' => $result['reason'] ?? 'invalid_token',
            ], 422);
        }

        return $this->json->success($response, [
            'confirmed' => true,
            'email' => $result['email'] ?? '',
        ], 200, Lang::get('confirm_success', [], 'newsletter'));
    }

    public function unsubscribe(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $token = trim((string) ($params['token'] ?? ''));
        $preference = isset($params['preference']) ? trim((string) $params['preference']) : null;
        if ($preference === '') {
            $preference = null;
        }

        if ($token === '') {
            return $this->json->error($response, Lang::get('token_required', [], 'newsletter'), 400);
        }

        $result = $this->newsletterRepository->unsubscribeByToken($token, $preference);
        if (!$result['ok']) {
            $message = ($result['reason'] ?? '') === 'invalid_preference'
                ? Lang::get('unsubscribe_invalid_preference', [], 'newsletter')
                : Lang::get('unsubscribe_invalid', [], 'newsletter');

            return $this->json->respond($response, [
                'success' => false,
                'message' => $message,
                'reason' => $result['reason'] ?? 'invalid_token',
            ], 422);
        }

        if (($result['reason'] ?? '') === 'already_unsubscribed') {
            return $this->json->success($response, [
                'unsubscribed' => true,
                'fullyUnsubscribed' => true,
                'email' => $result['email'] ?? '',
            ], 200, Lang::get('unsubscribe_already', [], 'newsletter'));
        }

        $fullyUnsubscribed = ($result['fullyUnsubscribed'] ?? true) === true;
        $message = $fullyUnsubscribed
            ? Lang::get('unsubscribe_success', [], 'newsletter')
            : Lang::get('unsubscribe_preference_success', [
                'preference' => Lang::get('preference.' . ($result['preference'] ?? ''), [], 'newsletter'),
            ], 'newsletter');

        return $this->json->success($response, [
            'unsubscribed' => true,
            'fullyUnsubscribed' => $fullyUnsubscribed,
            'preference' => $result['preference'] ?? null,
            'email' => $result['email'] ?? '',
        ], 200, $message);
    }

    public function manageGet(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $token = trim((string) ($params['token'] ?? ''));

        if ($token === '') {
            return $this->json->error($response, Lang::get('token_required', [], 'newsletter'), 400);
        }

        $result = $this->newsletterRepository->findByManageToken($token);
        if (!$result['ok']) {
            return $this->json->respond($response, [
                'success' => false,
                'message' => Lang::get('manage_invalid', [], 'newsletter'),
                'reason' => $result['reason'] ?? 'invalid_token',
            ], 422);
        }

        $newsletter = $this->settings->group('newsletter');
        $enabledPreferences = \PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences::parseEnabledList(
            (string) ($newsletter['enabledPreferences'] ?? '')
        );

        return $this->json->success($response, [
            'emailMasked' => $this->maskEmail((string) ($result['email'] ?? '')),
            'preferences' => $result['preferences'] ?? [],
            'status' => $result['status'] ?? 'active',
            'enabledPreferences' => $enabledPreferences,
            'requireConsentCheckbox' => ($newsletter['requireConsentCheckbox'] ?? false) === true,
        ]);
    }

    public function manageUpdate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'newsletter'), 400);
        }

        $token = trim((string) ($data['token'] ?? ''));
        if ($token === '') {
            return $this->json->error($response, Lang::get('token_required', [], 'newsletter'), 400);
        }

        $rawPreferences = $data['preferences'] ?? null;
        if (!is_array($rawPreferences)) {
            return $this->json->error($response, Lang::get('preferences_required', [], 'newsletter'), 422);
        }

        $newsletter = $this->settings->group('newsletter');
        $enabledPreferences = \PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences::parseEnabledList(
            (string) ($newsletter['enabledPreferences'] ?? '')
        );
        $preferences = \PaginiumCMS\Modules\Newsletter\Support\NewsletterPreferences::normalizeSelection(
            array_values(array_filter($rawPreferences, static fn (mixed $value): bool => is_string($value))),
            $enabledPreferences
        );

        if ($preferences === []) {
            return $this->json->error($response, Lang::get('preferences_required', [], 'newsletter'), 422);
        }

        $result = $this->newsletterRepository->updatePreferencesByToken($token, $preferences);
        if (!$result['ok']) {
            $message = match ($result['reason'] ?? '') {
                'unsubscribed' => Lang::get('manage_unsubscribed', [], 'newsletter'),
                default => Lang::get('manage_invalid', [], 'newsletter'),
            };

            return $this->json->respond($response, [
                'success' => false,
                'message' => $message,
                'reason' => $result['reason'] ?? 'invalid_token',
            ], 422);
        }

        return $this->json->success($response, [
            'preferences' => $result['preferences'] ?? [],
            'status' => $result['status'] ?? 'active',
        ], 200, Lang::get('manage_updated', [], 'newsletter'));
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }

        [$local, $domain] = explode('@', $email, 2);
        if ($local === '') {
            return '***@' . $domain;
        }

        $visible = mb_substr($local, 0, 1);

        return $visible . '***@' . $domain;
    }
}
