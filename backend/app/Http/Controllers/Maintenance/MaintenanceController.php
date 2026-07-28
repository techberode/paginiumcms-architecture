<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Maintenance;

use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Core\Settings\MaintenanceMode;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterSubscribeService;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MaintenanceController
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private NewsletterSubscribeService $subscribeService,
        private MessageRepositoryInterface $messageRepository,
        private Validator $validator,
        private JsonResponder $json
    ) {
    }

    public function subscribe(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!$this->isMaintenancePublicActionAllowed()) {
            return $this->json->error($response, Lang::get('inactive', [], 'maintenance'), 403);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'maintenance'), 400);
        }

        try {
            $validated = $this->validator->validate($data, [
                'email' => ['required', 'email', 'max:255'],
                'source' => ['string', 'in:coming_soon,under_maintenance'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'maintenance'),
                $e->getErrors()
            );
        }

        $maintenance = $this->settings->group('maintenance');
        if (($maintenance['newsletterEnabled'] ?? true) !== true) {
            return $this->json->error($response, Lang::get('newsletter_disabled', [], 'maintenance'), 403);
        }

        $mode = MaintenanceMode::resolve($maintenance);
        $source = (string) ($validated['source'] ?? $mode);

        $result = $this->subscribeService->subscribe($data, $source);
        if (!$result['ok']) {
            if (isset($result['errors'])) {
                return $this->json->validation(
                    $response,
                    $result['message'] ?? Lang::get('validation_failed', [], 'maintenance'),
                    $result['errors']
                );
            }

            return $this->json->error(
                $response,
                $result['message'] ?? Lang::get('invalid_payload', [], 'maintenance'),
                $result['status']
            );
        }

        return $this->json->success(
            $response,
            $result['payload'] ?? [],
            $result['status'],
            Lang::get('newsletter_subscribed', [], 'maintenance')
        );
    }

    public function sendMessage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $maintenance = $this->settings->group('maintenance');
        if (MaintenanceMode::resolve($maintenance) !== MaintenanceMode::UNDER_MAINTENANCE) {
            return $this->json->error($response, Lang::get('message_not_allowed', [], 'maintenance'), 403);
        }

        if (($maintenance['maintenanceShowContactForm'] ?? true) !== true) {
            return $this->json->error($response, Lang::get('contact_disabled', [], 'maintenance'), 403);
        }

        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'maintenance'), 400);
        }

        try {
            $validated = $this->validator->validate($data, [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'maintenance'),
                $e->getErrors()
            );
        }

        $message = new ContactMessage(
            (string) $validated['name'],
            (string) $validated['email'],
            (string) $validated['message']
        );

        $subject = trim((string) ($maintenance['maintenanceContactSubject'] ?? 'Správa z režimu údržby'));
        $message->setSubject($subject !== '' ? $subject : 'Správa z režimu údržby');
        $message->setPriority(ContactMessage::PRIORITY_HIGH);

        $serverParams = $request->getServerParams();
        $message->setIp((string) ($serverParams['REMOTE_ADDR'] ?? 'unknown'));

        $this->messageRepository->save($message);

        return $this->json->success(
            $response,
            ['id' => $message->getId()],
            201,
            Lang::get('message_sent', [], 'maintenance')
        );
    }

    private function isMaintenancePublicActionAllowed(): bool
    {
        return MaintenanceMode::isActive($this->settings->group('maintenance'));
    }
}
