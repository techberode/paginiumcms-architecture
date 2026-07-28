<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Newsletter;

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

        $data = json_decode((string) $request->getBody(), true);
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

        if ($token === '') {
            return $this->json->error($response, Lang::get('token_required', [], 'newsletter'), 400);
        }

        $result = $this->newsletterRepository->unsubscribeByToken($token);
        if (!$result['ok']) {
            return $this->json->respond($response, [
                'success' => false,
                'message' => Lang::get('unsubscribe_invalid', [], 'newsletter'),
                'reason' => $result['reason'] ?? 'invalid_token',
            ], 422);
        }

        $message = ($result['reason'] ?? '') === 'already_unsubscribed'
            ? Lang::get('unsubscribe_already', [], 'newsletter')
            : Lang::get('unsubscribe_success', [], 'newsletter');

        return $this->json->success($response, [
            'unsubscribed' => true,
            'email' => $result['email'] ?? '',
        ], 200, $message);
    }
}
