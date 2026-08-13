<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\Validation\ValidationException;
use PaginiumCMS\Core\Validation\Validator;
use PaginiumCMS\Http\Support\JsonResponder;
use PaginiumCMS\Modules\Newsletter\Contracts\NewsletterRepositoryInterface;
use PaginiumCMS\Modules\Newsletter\Services\NewsletterMailService;
use PaginiumCMS\Support\AppVersion;
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
        $payload = RequestJsonBody::decode($request);
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

    public function sendCmsRelease(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'newsletter'), 400);
        }

        try {
            $this->validator->validate($payload, [
                'version' => ['string', 'max:40'],
                'title' => ['required', 'string', 'min:2', 'max:200'],
                'body' => ['required', 'string', 'min:2', 'max:5000'],
                'url' => ['url', 'max:500'],
            ]);
        } catch (ValidationException $e) {
            return $this->json->validation(
                $response,
                Lang::get('validation_failed', [], 'newsletter'),
                $e->getErrors()
            );
        }

        $version = trim((string) ($payload['version'] ?? AppVersion::current()));
        $title = trim((string) ($payload['title'] ?? ''));
        $body = trim((string) ($payload['body'] ?? ''));
        $url = isset($payload['url']) ? trim((string) $payload['url']) : null;
        if ($url === '') {
            $url = null;
        }

        $result = $this->mailService->sendCmsRelease($version, $title, $body, $url);

        if ($result['sent'] > 0) {
            return $this->json->success(
                $response,
                $result,
                200,
                Lang::get('admin.cms_release_sent', [], 'newsletter')
            );
        }

        $reason = (string) ($result['reason'] ?? 'nothing_sent');
        $message = match ($reason) {
            'send_disabled' => Lang::get('admin.send_disabled', [], 'newsletter'),
            'cms_release_disabled' => Lang::get('admin.cms_release_disabled', [], 'newsletter'),
            'email_not_configured' => Lang::get('admin.email_not_configured', [], 'newsletter'),
            'no_subscribers' => Lang::get('admin.no_cms_release_subscribers', [], 'newsletter'),
            'invalid_payload' => Lang::get('validation_failed', [], 'newsletter'),
            default => Lang::get('admin.send_failed', [], 'newsletter'),
        };

        return $this->json->respond($response, [
            'success' => false,
            'message' => $message,
            'data' => $result,
        ], in_array($reason, ['no_subscribers'], true) ? 200 : 422);
    }

    /**
     * @param array<string, string> $args
     */
    public function unsubscribeSubscriber(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = trim((string) ($args['id'] ?? ''));
        if ($id === '') {
            return $this->json->error($response, Lang::get('admin.subscriber_not_found', [], 'newsletter'), 404);
        }

        $result = $this->newsletterRepository->unsubscribeById($id);
        if (!$result['ok']) {
            return $this->json->error($response, Lang::get('admin.subscriber_not_found', [], 'newsletter'), 404);
        }

        return $this->json->success(
            $response,
            ['id' => $id, 'status' => 'unsubscribed'],
            200,
            Lang::get('admin.subscriber_unsubscribed', [], 'newsletter')
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function deleteSubscriber(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = trim((string) ($args['id'] ?? ''));
        if ($id === '') {
            return $this->json->error($response, Lang::get('admin.subscriber_not_found', [], 'newsletter'), 404);
        }

        if (!$this->newsletterRepository->deleteById($id)) {
            return $this->json->error($response, Lang::get('admin.subscriber_not_found', [], 'newsletter'), 404);
        }

        return $this->json->success(
            $response,
            ['id' => $id],
            200,
            Lang::get('admin.subscriber_deleted', [], 'newsletter')
        );
    }

    public function bulkUnsubscribe(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->bulkSubscriberAction($request, $response, 'unsubscribe');
    }

    public function bulkDelete(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->bulkSubscriberAction($request, $response, 'delete');
    }

    private function bulkSubscriberAction(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $action
    ): ResponseInterface {
        $payload = RequestJsonBody::decode($request);
        if (!is_array($payload)) {
            return $this->json->error($response, Lang::get('invalid_payload', [], 'newsletter'), 400);
        }

        $ids = $this->normalizeIds($payload['ids'] ?? null);
        if ($ids === []) {
            return $this->json->error($response, Lang::get('admin.ids_required', [], 'newsletter'), 400);
        }

        $batch = $action === 'delete'
            ? $this->newsletterRepository->bulkDelete($ids)
            : $this->newsletterRepository->bulkUnsubscribe($ids);

        $message = $action === 'delete'
            ? Lang::get('admin.bulk_deleted', [], 'newsletter')
            : Lang::get('admin.bulk_unsubscribed', [], 'newsletter');

        return $this->json->success($response, $batch->toArray(), 200, $message);
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function normalizeIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($id) => trim((string) $id), $value),
            static fn (string $id) => $id !== ''
        ));
    }
}
