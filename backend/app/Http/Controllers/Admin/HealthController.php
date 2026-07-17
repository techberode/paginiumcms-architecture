<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Health\Services\HealthCheckManager;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin health check API (Iteration 7).
 */
final class HealthController
{
    public function __construct(
        private HealthCheckManager $healthManager,
        private JsonResponder $json
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $group = isset($params['group']) ? (string) $params['group'] : null;

        $report = $this->healthManager->run($group);

        return $this->json->success(
            $response,
            $this->normalizeReport($report->toArray()),
            $report->isPass() ? 200 : 500
        );
    }

    public function check(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $name = (string) $request->getAttribute('name');
        $result = $this->healthManager->runCheck($name);

        if ($result === null) {
            return $this->json->error($response, 'Health check not found: ' . $name, 404);
        }

        $payload = $this->normalizeCheck($result->toArray());

        return $this->json->respond($response, [
            'success' => $result->isPass(),
            'data' => $payload,
        ], $result->isPass() ? 200 : 500);
    }

    public function checks(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'checks' => $this->healthManager->getAvailableChecks(),
            'groups' => $this->healthManager->getGroups(),
        ]);
    }

    /**
     * @param array<int|string, mixed> $report
     * @return array<int|string, mixed>
     */
    private function normalizeReport(array $report): array
    {
        if (!isset($report['checks']) || !is_array($report['checks'])) {
            return $report;
        }

        $report['checks'] = array_map(
            fn (array $check): array => $this->normalizeCheck($check),
            $report['checks']
        );

        return $report;
    }

    /**
     * @param array<int|string, mixed> $check
     * @return array<int|string, mixed>
     */
    private function normalizeCheck(array $check): array
    {
        if (isset($check['check']) && !isset($check['name'])) {
            $check['name'] = $check['check'];
        }

        return $check;
    }
}
