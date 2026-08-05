<?php

declare(strict_types=1);

namespace PaginiumCMS\Http\Controllers\Admin;

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
        private JsonResponder $json
    ) {
    }

    public function summary(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json->success($response, [
            'config' => $this->settings->publicSummary(),
            'summary' => $this->aggregator->summary(),
            'recent_breaches' => $this->breaches->recent(),
            'host_metrics_note' => 'Host CPU/RAM/disk metrics remain under It.46 — not conflated with PHP APM.',
        ]);
    }

    public function clearSamples(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->samples->clear();
        $this->breaches->clear();

        return $this->json->success($response, ['cleared' => true], 200, 'APM samples cleared');
    }
}
