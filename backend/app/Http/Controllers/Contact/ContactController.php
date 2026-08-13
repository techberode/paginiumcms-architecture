<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Contact;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContactController
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private Validator $validator,
        private JsonResponder $json
    ) {
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
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

        $message = new ContactMessage(
            (string) $validated['name'],
            (string) $validated['email'],
            (string) $validated['message']
        );

        $subject = trim((string) ($validated['subject'] ?? ''));
        if ($subject !== '') {
            $message->setSubject($subject);
        }

        $serverParams = $request->getServerParams();
        $message->setIp((string) ($serverParams['REMOTE_ADDR'] ?? 'unknown'));

        $this->messageRepository->save($message);

        return $this->json->success(
            $response,
            ['id' => $message->getId()],
            201,
            Lang::get('submitted', [], 'contact')
        );
    }
}
