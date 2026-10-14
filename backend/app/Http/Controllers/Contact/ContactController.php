<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Contact;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Core\Validation\VisitorEmailGuard;
use PaginiumCMS\Core\Validation\VisitorPhoneGuard;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Messages\Models\ContactMessage;
use PaginiumCMS\Modules\Messages\Services\MessageDeskService;
use PaginiumCMS\Support\LogSanitizer;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ContactController
{
    public function __construct(
        private Validator $validator,
        private JsonResponder $json,
        private MessageDeskService $desk,
        private VisitorEmailGuard $visitorEmail,
        private VisitorPhoneGuard $visitorPhone
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

        $reportedUrl = trim((string) ($data['reportedUrl'] ?? ''));
        $isBrokenContentReport = $reportedUrl !== '';

        try {
            $rules = [
                'name' => ['required', 'string', 'min:2', 'max:120'],
                'email' => ['required', 'email', 'max:255'],
                'phonePrefix' => ['string', 'max:8'],
                'phoneNumber' => ['string', 'max:16'],
                'phone' => ['string', 'max:20'],
                'subject' => ['string', 'max:200'],
                'message' => ['required', 'string', 'min:10', 'max:5000'],
                'reportedUrl' => ['string', 'max:500'],
                'referer' => ['string', 'max:500'],
            ];
            if ($isBrokenContentReport) {
                $rules['reportedUrl'] = ['required', 'string', 'min:1', 'max:500'];
                $rules['message'] = ['string', 'max:5000'];
            }

            $validated = $this->validator->validate($data, $rules);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'contact'),
                $e->getErrors()
            );
        }

        try {
            $email = $this->visitorEmail->normalize((string) $validated['email'], 'contact');
            $phone = $isBrokenContentReport ? '' : $this->normalizePhone($data, $validated);
        } catch (ValidationException $e) {
            return $this->json->validation($response, Lang::get('validation_failed', [], 'contact'), $e->getErrors());
        }

        $userMessage = trim((string) ($validated['message'] ?? ''));
        if ($isBrokenContentReport) {
            $safeUrl = $this->normalizeReportedUrl($reportedUrl);
            if ($safeUrl === null) {
                return $this->json->validation(
                    $response,
                    Lang::get('validation_failed', [], 'contact'),
                    ['reportedUrl' => [Lang::get('broken_url_invalid', [], 'contact')]]
                );
            }

            $referer = LogSanitizer::value(trim((string) ($validated['referer'] ?? '')));
            $body = "Hlásená URL: {$safeUrl}\n";
            if ($referer !== '') {
                $body .= "Referer: {$referer}\n";
            }
            $body .= "---\n";
            $body .= $userMessage !== '' ? $userMessage : Lang::get('broken_report_default_note', [], 'contact');

            $message = new ContactMessage((string) $validated['name'], $email, $body);
            $message->setPhone($phone);
            $message->setSubject(ContactMessage::SUBJECT_BROKEN_CONTENT);
            $message->setPriority(ContactMessage::PRIORITY_HIGH);
            $message->setChannel('broken_content');
        } else {
            $message = new ContactMessage(
                (string) $validated['name'],
                $email,
                $userMessage
            );
            $message->setPhone($phone);

            $registrationRequest = filter_var($data['registrationRequest'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $subject = trim((string) ($validated['subject'] ?? ''));
            if ($registrationRequest) {
                $message->setRegistrationRequest(true);
                $message->setSubject($subject !== '' ? $subject : ContactMessage::SUBJECT_REGISTRATION);
            } elseif ($subject !== '') {
                $message->setSubject($subject);
            }
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

    private function normalizeReportedUrl(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '' || str_contains($trimmed, '://') || str_starts_with($trimmed, '//')) {
            return null;
        }

        $path = str_starts_with($trimmed, '/') ? $trimmed : '/' . ltrim($trimmed, '/');
        if (strlen($path) > 500 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $path) === 1) {
            return null;
        }

        return $path;
    }

    /**
     * @param array<int|string, mixed> $data
     * @param array<int|string, mixed> $validated
     */
    private function normalizePhone(array $data, array $validated): string
    {
        $prefix = trim((string) ($validated['phonePrefix'] ?? $data['phonePrefix'] ?? ''));
        $number = trim((string) ($validated['phoneNumber'] ?? $data['phoneNumber'] ?? ''));
        if ($prefix !== '' || $number !== '') {
            return $this->visitorPhone->normalize($prefix, $number);
        }

        return $this->visitorPhone->normalizeCombined((string) ($validated['phone'] ?? $data['phone'] ?? ''));
    }
}
