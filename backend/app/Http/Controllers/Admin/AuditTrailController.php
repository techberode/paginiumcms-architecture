<?php
// backend/app/Http/Controllers/Admin/AuditTrailController.php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use PaginiumCMS\Support\JsonHelper;

class AuditTrailController
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * GET /api/admin/audit/content/{contentId}
     * Získa audit trail pre konkrétny obsah
 * @param array<int|string, mixed> $args
 */public function getContentAudit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $contentId = $args['contentId'] ?? '';
        $params = $request->getQueryParams();
        $limit = (int)($params['limit'] ?? 100);

        try {
            $auditTrail = $this->auditTrailService->getContentAuditTrail($contentId, $limit);
            
            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => [
                    'content_id' => $contentId,
                    'total' => count($auditTrail),
                    'events' => $auditTrail
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/audit/user/{userId}
     * Získa audit trail pre používateľa
 * @param array<int|string, mixed> $args
 */public function getUserAudit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $args['userId'] ?? '';
        $params = $request->getQueryParams();
        $limit = (int)($params['limit'] ?? 100);

        try {
            $auditTrail = $this->auditTrailService->getUserAuditTrail($userId, $limit);
            
            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'total' => count($auditTrail),
                    'events' => $auditTrail
                ]
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/audit/stats
     * Získa štatistiky auditu
     */
    public function getStats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = array_filter($params, function($key) {
            return in_array($key, ['category', 'action', 'user_id', 'severity']);
        }, ARRAY_FILTER_USE_KEY);

        try {
            $stats = $this->auditTrailService->getAuditStats($filters);
            
            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'data' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/admin/audit/export
     * Exportuje audit do CSV
     */
    public function exportAudit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $filters = array_filter($params, function($key) {
            return in_array($key, ['category', 'action', 'user_id', 'severity']);
        }, ARRAY_FILTER_USE_KEY);

        try {
            $csv = $this->auditTrailService->exportAuditToCsv($filters);
            
            $response->getBody()->write($csv);
            return $response
                ->withHeader('Content-Type', 'text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="audit_trail_' . date('Y-m-d') . '.csv"');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/admin/audit/log
     * Ručné logovanie audit udalosti
     */
    public function logEvent(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string)$request->getBody(), true);
        
        $category = $data['category'] ?? '';
        $target = $data['target'] ?? '';
        $action = $data['action'] ?? '';
        $metadata = $data['metadata'] ?? [];
        $severity = $data['severity'] ?? 'INFO';

        if (empty($category) || empty($target) || empty($action)) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => 'Category, target and action are required'
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $user = $request->getAttribute('user');
            $this->auditTrailService->logAdminAction($action, $target, $user, $metadata, $severity);
            
            $response->getBody()->write(JsonHelper::encode([
                'success' => true,
                'message' => 'Audit event logged successfully'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(JsonHelper::encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
}
