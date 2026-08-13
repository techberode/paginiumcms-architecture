<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Http\Support\RequestJsonBody;
use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuditTrailController
{
    public function __construct(
        private AuditTrailService $auditTrailService,
        private JsonResponder $json
    ) {
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function getContentAudit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = (string) ($args['contentId'] ?? '');
        $params = $request->getQueryParams();
        $limit = (int) ($params['limit'] ?? 100);

        try {
            $auditTrail = $this->auditTrailService->getContentAuditTrail($contentId, $limit);

            return $this->json->success($response, [
                'content_id' => $contentId,
                'total' => count($auditTrail),
                'events' => $auditTrail,
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    /**
     * @param array<int|string, mixed> $args
     */
    public function getUserAudit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = (string) ($args['userId'] ?? '');
        $params = $request->getQueryParams();
        $limit = (int) ($params['limit'] ?? 100);

        try {
            $auditTrail = $this->auditTrailService->getUserAuditTrail($userId, $limit);

            return $this->json->success($response, [
                'user_id' => $userId,
                'total' => count($auditTrail),
                'events' => $auditTrail,
            ]);
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function getStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = array_filter(
            $params,
            static fn (string $key): bool => in_array($key, ['category', 'action', 'user_id', 'severity'], true),
            ARRAY_FILTER_USE_KEY
        );

        try {
            return $this->json->success($response, $this->auditTrailService->getAuditStats($filters));
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function exportAudit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = array_filter(
            $params,
            static fn (string $key): bool => in_array($key, ['category', 'action', 'user_id', 'severity'], true),
            ARRAY_FILTER_USE_KEY
        );

        try {
            $csv = $this->auditTrailService->exportAuditToCsv($filters);

            $response->getBody()->write($csv);

            return $response
                ->withHeader('Content-Type', 'text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="audit_trail_' . date('Y-m-d') . '.csv"');
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }

    public function logEvent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = RequestJsonBody::decode($request);
        if (!is_array($data)) {
            return $this->json->error($response, 'Invalid JSON body', 400);
        }

        $category = (string) ($data['category'] ?? '');
        $target = (string) ($data['target'] ?? '');
        $action = (string) ($data['action'] ?? '');
        $metadata = is_array($data['metadata'] ?? null) ? $data['metadata'] : [];
        $severity = (string) ($data['severity'] ?? 'INFO');

        if ($category === '' || $target === '' || $action === '') {
            return $this->json->error($response, 'Category, target and action are required', 400);
        }

        try {
            $user = $request->getAttribute('user');
            $this->auditTrailService->logAdminAction($action, $target, $user, $metadata, $severity);

            return $this->json->success($response, null, 200, 'Audit event logged successfully');
        } catch (\Exception $e) {
            return $this->json->error($response, $e->getMessage(), 500);
        }
    }
}
