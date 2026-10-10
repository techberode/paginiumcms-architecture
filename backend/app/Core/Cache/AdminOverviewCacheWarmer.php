<?php

declare(strict_types=1);

namespace PaginiumCMS\Core\Cache;

use PaginiumCMS\Core\AuditTrail\Services\AuditTrailService;
use PaginiumCMS\Core\HybridEngine\QueryIndex\QueryIndexAdvisor;
use PaginiumCMS\Core\Performance\AdminLoadHintResolver;
use PaginiumCMS\Core\Performance\HostMetricsService;
use PaginiumCMS\Core\Performance\PerformanceAggregator;
use PaginiumCMS\Core\Performance\PerformanceBreachStore;
use PaginiumCMS\Core\Performance\PerformanceGuardSettings;
use PaginiumCMS\Core\Scheduler\Services\AdminJobsOverviewProvider;
use PaginiumCMS\Core\Settings\Contracts\SettingsRepositoryInterface;
use PaginiumCMS\Modules\ProjectPlanner\Contracts\ProjectPlanRepositoryInterface;
use PaginiumCMS\Modules\ProjectPlanner\Services\ProjectPlanApiPresenter;

/**
 * Pre-computes P2 admin overview caches (CLI / cron — Iteration 100c).
 */
final class AdminOverviewCacheWarmer
{
    public function __construct(
        private AdminOverviewCacheService $cache,
        private AuditTrailService $auditTrail,
        private AdminJobsOverviewProvider $jobsOverview,
        private PerformanceGuardSettings $performanceSettings,
        private PerformanceAggregator $aggregator,
        private PerformanceBreachStore $breaches,
        private QueryIndexAdvisor $queryIndexAdvisor,
        private AdminLoadHintResolver $loadHint,
        private HostMetricsService $hostMetrics,
        private SettingsRepositoryInterface $settings,
        private ProjectPlanRepositoryInterface $projectPlans,
        private ProjectPlanApiPresenter $projectPlanPresenter,
    ) {
    }

    /**
     * @return list<string> warmed segment labels
     */
    public function warmAll(): array
    {
        $warmed = [];

        $this->cache->refreshAuditStats(fn (): array => $this->auditTrail->getAuditStats([]));
        $warmed[] = 'audit_stats';

        $this->cache->refreshJobsOverview(
            fn (): array => $this->jobsOverview->build(AdminJobsOverviewProvider::DEFAULT_RECENT_RUNS_LIMIT),
            AdminJobsOverviewProvider::DEFAULT_RECENT_RUNS_LIMIT
        );
        $warmed[] = 'jobs_overview';

        $this->cache->refreshJobsOverview(
            fn (): array => $this->jobsOverview->build(AdminJobsOverviewProvider::DASHBOARD_RECENT_RUNS_LIMIT),
            AdminJobsOverviewProvider::DASHBOARD_RECENT_RUNS_LIMIT
        );
        $warmed[] = 'jobs_overview_dashboard';

        $this->cache->refreshApmSummary(fn (): array => [
            'config' => $this->performanceSettings->publicSummary(),
            'summary' => $this->aggregator->summary(),
            'recent_breaches' => $this->breaches->recent(),
            'advisor_hints' => $this->queryIndexAdvisor->activeHints(),
            'load_hint' => $this->loadHint->resolve(),
            'host_metrics' => $this->hostMetrics->publicView(),
        ]);
        $warmed[] = 'metrics_apm';

        $planner = $this->settings->group('projectPlanner');
        $plannerEnabled = $planner['enabled'] ?? true;
        if ($plannerEnabled !== false && $plannerEnabled !== 0 && $plannerEnabled !== '0') {
            $this->cache->refreshProjectPlansOverview(
                fn (): array => $this->projectPlanPresenter->overview($this->projectPlans->findAll())
            );
            $warmed[] = 'project_plans_overview';
        }

        return $warmed;
    }
}
