<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

use PaginiumCMS\Core\Cache\AdminOverviewCacheService;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdvisor;
use PaginiumCMS\Core\Performance\AdminLoadHintResolver;
use PaginiumCMS\Core\Performance\HostMetricsIngestException;
use PaginiumCMS\Core\Performance\HostMetricsIngestGuard;
use PaginiumCMS\Core\Performance\HostMetricsService;
use PaginiumCMS\Core\Performance\HostMetricsSnapshotSanitizer;
use PaginiumCMS\Core\Performance\HostMetricsStore;
use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceBreachStore;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use PaginiumCMS\Http\Support\JsonResponder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Admin Performance Guard / APM metrics (Iteration 71).
 */
final class MetricsController
{
    public function __construct(
        private PerformanceGuardSettings $settings,
        private PerformanceAggregator $aggregator,
        private PerformanceBreachStore $breaches,
        private PerformanceSampleStore $samples,
        private QueryIndexAdvisor $queryIndexAdvisor,
        private AdminOverviewCacheService $adminOverviewCache,
        private AdminLoadHintResolver $loadHint,
        private HostMetricsService $hostMetrics,
        private HostMetricsStore $hostMetricsStore,
        private HostMetricsIngestGuard $hostMetricsIngest,
        private JsonResponder $json
    ) {
    }

    public function loadHint(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->loadHint->resolve());
    }

    public function host(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, $this->hostMetrics->publicView());
    }

    public function ingestHost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $token = trim($request->getHeaderLine('X-Host-Metrics-Token'));
        if ($token === '') {
            return $this->json->error($response, 'host_metrics_token_required', 401);
        }

        try {
            $this->hostMetricsIngest->assertAllowed($request->getServerParams(), $token);
        } catch (HostMetricsIngestException $exception) {
            return $this->json->error($response, $exception->errorCode, $exception->statusCode);
        }

        $body = (string) $request->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return $this->json->error($response, 'host_metrics_invalid_json', 422);
        }

        $snapshot = HostMetricsSnapshotSanitizer::sanitize($decoded);
        if ($snapshot === null) {
            return $this->json->error($response, 'host_metrics_invalid_payload', 422);
        }

        $this->hostMetricsStore->save($snapshot);

        return $this->json->success($response, ['saved' => true, 'collected_at' => $snapshot['collected_at'] ?? null]);
    }

    public function summary(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $payload = $this->adminOverviewCache->rememberApmSummary(function (): array {
            return [
                'config' => $this->settings->publicSummary(),
                'summary' => $this->aggregator->summary(),
                'recent_breaches' => $this->breaches->recent(),
                'advisor_hints' => $this->queryIndexAdvisor->activeHints(),
                'load_hint' => $this->loadHint->resolve(),
                'host_metrics' => $this->hostMetrics->publicView(),
            ];
        });

        return $this->json->success($response, $payload);
    }

    public function clearSamples(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->samples->clear();
        $this->breaches->clear();
        $this->adminOverviewCache->invalidateApmSummary();

        return $this->json->success($response, ['cleared' => true], 200, 'APM samples cleared');
    }
}
