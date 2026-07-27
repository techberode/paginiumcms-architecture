<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Newsletter;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NewsletterController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NewsletterRepositoryInterface $newsletterRepository,
        private Validator $validator,
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

        // Honeypot — bots fill hidden fields; pretend success without storing PII.
        $honeypot = trim((string) ($data['_hp'] ?? ''));
        if ($honeypot !== '') {
            return $this->json->success(
                $response,
                ['id' => 'hp_' . bin2hex(random_bytes(8)), 'created' => true],
                201,
                Lang::get('subscribed', [], 'newsletter')
            );
        }

        try {
            $validated = $this->validator->validate($data, [
                'email' => ['required', 'email', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'newsletter'),
                $e->getErrors()
            );
        }

        $result = $this->newsletterRepository->subscribe((string) $validated['email'], 'footer');

        return $this->json->success(
            $response,
            ['id' => $result['id'], 'created' => $result['created']],
            $result['created'] ? 201 : 200,
            Lang::get($result['created'] ? 'subscribed' : 'exists', [], 'newsletter')
        );
    }
}
