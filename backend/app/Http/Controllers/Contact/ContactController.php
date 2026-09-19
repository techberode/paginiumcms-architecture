<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Contact;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageDeskService;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContactController
{
    public function __construct(
        private Validator $validator,
        private JsonResponder $json,
        private MessageDeskService $desk,
        private VisitorEmailGuard $visitorEmail
    ) {
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'contact'), 400);
        }

        if (trim((string) ($data['_hp'] ?? '')) !== '') {
            return $this->json->success(
                $response,
                ['id' => 'hp_' . bin2hex(random_bytes(8))],
                201,
                Lang::get('submitted', [], 'contact')
            );
        }

        try {
            $validated = $this->validator->validate($data, [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'subject' => ['string', 'max:200'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'contact'),
                $e->getErrors()
            );
        }

        try {
            $email = $this->visitorEmail->normalize((string) $validated['email'], 'contact');
        } catch (ValidationException $e) {
            return $this->json->validation($response, Lang::get('validation_failed', [], 'contact'), $e->getErrors());
        }

        $message = new ContactMessage(
            (string) $validated['name'],
            $email,
            (string) $validated['message']
        );

        $registrationRequest = filter_var($data['registrationRequest'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $subject = trim((string) ($validated['subject'] ?? ''));
        if ($registrationRequest) {
            $message->setRegistrationRequest(true);
            $message->setSubject($subject !== '' ? $subject : ContactMessage::SUBJECT_REGISTRATION);
        } elseif ($subject !== '') {
            $message->setSubject($subject);
        }

        $serverParams = $request->getServerParams();
        $message->setIp((string) ($serverParams['REMOTE_ADDR'] ?? 'unknown'));

        $result = $this->desk->ingest($message);

        return $this->json->success(
            $response,
            ['id' => $result['message']->getId(), 'appended' => $result['appended']],
            201,
            Lang::get('submitted', [], 'contact')
        );
    }
}
