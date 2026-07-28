<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService;
use PaginiumCMS\Support\Lang;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NewsletterAdminController
{
    public function __construct(
        private NewsletterRepositoryInterface $newsletterRepository,
        private NewsletterMailService $mailService,
        private Validator $validator,
        private JsonResponder $json
    ) {
    }

    public function listSubscribers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $items = $this->newsletterRepository->findAll();

        return $this->json->success($response, [
            'items' => $items,
            'count' => count($items),
            'bySource' => $this->newsletterRepository->countBySource(),
        ]);
    }

    public function exportSubscribers(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $csv = $this->newsletterRepository->exportCsv();
        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    }

    public function sendStatus(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->mailService->status());
    }

    public function sendWeeklyDigestNow(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $result = $this->mailService->sendWeeklyDigest();

        if ($result['sent'] > 0) {
            return $this->json->success(
                $response,
                $result,
                200,
                Lang::get('admin.weekly_digest_sent', [], 'newsletter')
            );
        }

        $reason = (string) ($result['reason'] ?? 'nothing_sent');
        $message = match ($reason) {
            'send_disabled' => Lang::get('admin.send_disabled', [], 'newsletter'),
            'weekly_digest_disabled' => Lang::get('admin.weekly_digest_disabled', [], 'newsletter'),
            'email_not_configured' => Lang::get('admin.email_not_configured', [], 'newsletter'),
            'no_articles' => Lang::get('admin.no_articles', [], 'newsletter'),
            'no_subscribers' => Lang::get('admin.no_subscribers', [], 'newsletter'),
            default => Lang::get('admin.send_failed', [], 'newsletter'),
        };

        return $this->json->respond($response, [
            'success' => false,
            'message' => $message,
            'data' => $result,
        ], in_array($reason, ['no_articles', 'no_subscribers'], true) ? 200 : 422);
    }

    public function sendTest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = json_decode((string) $request->getBody(), true);
        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'newsletter'), 400);
        }

        try {
            $this->validator->validate($payload, [
                'email' => ['required', 'email', 'max:200'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'newsletter'),
                $e->getErrors()
            );
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if (!$this->mailService->isEmailConfigured()) {
            return $this->json->respond($response, [
                'success' => false,
                'message' => Lang::get('admin.email_not_configured', [], 'newsletter'),
            ], 422);
        }

        $ok = $this->mailService->sendTestEmail($email);

        return $this->json->respond($response, [
            'success' => $ok,
            'message' => $ok
                ? Lang::get('admin.test_sent', [], 'newsletter')
                : Lang::get('admin.test_failed', [], 'newsletter'),
        ], $ok ? 200 : 502);
    }
}
