<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Middleware;

use PaginiumCMS\Core\Cache\CacheManager;
use PaginiumCMS\Core\Performance\PerformanceContext;
use PaginiumCMS\Core\Performance\PerformanceGuardPolicy;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Performance\PerformanceIncidentService;
use PaginiumCMS\Core\Performance\PerformanceRouteLabelResolver;
use PaginiumCMS\Core\Performance\PerformanceSampleStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * In-request APM middleware (Iteration 71).
 */
final class PerformanceGuardMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PerformanceGuardSettings $settings,
        private PerformanceGuardPolicy $policy,
        private PerformanceContext $context,
        private PerformanceSampleStore $samples,
        private PerformanceRouteLabelResolver $routeLabels,
        private PerformanceIncidentService $incidents,
        private CacheManager $cache
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->settings->enabled() || !$this->policy->shouldSample() || !$this->shouldSamplePath($request)) {
            return $handler->handle($request);
        }

        $this->context->reset();
        $memoryStart = memory_get_usage(true);
        $started = hrtime(true);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $this->recordSample($request, 500, $started, $memoryStart);
            throw $e;
        }

        $this->recordSample($request, $response->getStatusCode(), $started, $memoryStart);

        return $response;
    }

    private function recordSample(
        ServerRequestInterface $request,
        int $status,
        int $startedNs,
        int $memoryStart
    ): void {
        $durationMs = (hrtime(true) - $startedNs) / 1_000_000;
        $route = $this->routeLabels->resolve($request);
        $cacheMetrics = $this->cache->metrics();

        $sample = [
            'ts' => time(),
            'route' => $route,
            'method' => $request->getMethod(),
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'memory_delta_mb' => round((memory_get_usage(true) - $memoryStart) / 1024 / 1024, 2),
            'storage_reads' => $this->context->storageReads(),
            'storage_writes' => $this->context->storageWrites(),
            'cache_hits' => $cacheMetrics['hits'],
            'cache_misses' => $cacheMetrics['misses'],
        ];

        $this->samples->append($sample);
        $this->incidents->recordLatencyBreach($route, $durationMs);
        $this->context->deactivate();
    }

    private function shouldSamplePath(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        // Large binary/media responses are not API latency — they skew p95 (ISS-158).
        if (str_starts_with($path, '/storage/')) {
            return false;
        }

        return true;
    }
}
