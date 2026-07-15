<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Contact;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Modules\Messages\Contracts\MessageRepositoryInterface;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContactController
{
    public function __construct(
        private MessageRepositoryInterface $messageRepository,
        private Validator $validator
    ) {
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->jsonError($response, Lang::get('invalid_payload', [], 'contact'), 400);
        }

        try {
            $validated = $this->validator->validate($data, [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'subject' => ['string', 'max:200'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
            ]);
        } catch (ValidationException $e) {
            return $this->jsonValidationError($response, $e);
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

        return $this->jsonSuccess(
            $response,
            ['id' => $message->getId()],
            Lang::get('submitted', [], 'contact'),
            201
        );
    }

    private function jsonSuccess(ResponseInterface $response, mixed $data, ?string $message = null, int $status = 200): ResponseInterface
    {
        $payload = ['success' => true, 'data' => $data];
        if ($message !== null) {
            $payload['message'] = $message;
        }

        $response->getBody()->write(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonError(ResponseInterface $response, string $message, int $status = 400): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    private function jsonValidationError(ResponseInterface $response, ValidationException $e): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => Lang::get('validation_failed', [], 'contact'),
            'errors' => $e->getErrors(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->withStatus(422)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
