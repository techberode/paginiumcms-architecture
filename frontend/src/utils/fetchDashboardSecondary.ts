import { getApmOverview, type ApmOverview } from '../api/metrics';
import { getJobsOverviewForDashboard, type JobsOverview } from '../api/jobs';
import { adminP2StaggerMs, sleep } from './adminP2Stagger';

export type DashboardSecondaryData = {
  recentActivity: Array<Record<string, unknown>>;
  apm: ApmOverview | null;
  jobs: JobsOverview | null;
};

type AuditStatsGet = <T>(url: string) => Promise<{ success: boolean; data?: T }>;

/**
 * Loads dashboard P2 panels sequentially to avoid browser connection queue + PHP session lock pile-ups (It.100).
 */
export async function fetchDashboardSecondary(
  get: AuditStatsGet,
  adminLoadBusy: boolean
): Promise<DashboardSecondaryData> {
  const stagger = adminP2StaggerMs(adminLoadBusy);

  const auditRes = await get<{ recent_events?: Array<Record<string, unknown>> }>('/api/admin/audit/stats');
  await sleep(stagger);

  const apm = await getApmOverview();
  await sleep(stagger);

  const jobs = await getJobsOverviewForDashboard();

  return {
    recentActivity: auditRes.success ? auditRes.data?.recent_events ?? [] : [],
    apm,
    jobs,
  };
}
