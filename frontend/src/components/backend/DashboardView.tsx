// frontend/src/components/backend/DashboardView.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import {
  FileText,
  BookOpen,
  HardDrive,
  Users,
  PlusCircle,
  Sparkles,
  ArrowUpRight,
  Mail,
  Image as ImageIcon,
  Settings,
  Clock3,
} from 'lucide-react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { useAdminListQuery } from '../../hooks/useAdminListQuery';
import { queryKeys } from '../../api/queryKeys';
import { getDashboardOverview, DashboardOverview } from '../../api/dashboard';
import { getApmOverview, ApmOverview } from '../../api/metrics';
import { AnalyticsChart } from '../dashboard/AnalyticsChart';
import { LocksPanel } from '../dashboard/LocksPanel';
import { ConflictsPanel } from '../dashboard/ConflictsPanel';
import { HealthPanel } from '../dashboard/HealthPanel';
import { PerformanceGuardPanel } from '../dashboard/PerformanceGuardPanel';
import { LogsPanel } from '../dashboard/LogsPanel';
import { DashboardActivityPanel } from '../dashboard/DashboardActivityPanel';
import { DashboardDiskStructurePanel } from '../dashboard/DashboardDiskStructurePanel';
import { SystemUpdateBanner } from '../dashboard/SystemUpdateBanner';
import { ProjectPlannerSummaryWidget } from './ProjectPlannerSummaryWidget';
import { AdminPageSkeleton } from '../ui/AdminPageSkeleton';
import { AdminEmptyState } from '../ui/AdminEmptyState';
import { AdminKpiCard } from '../ui/AdminKpiCard';
import { AdminWidgetCard } from '../ui/AdminWidgetCard';
import { AdminToolbar } from '../ui/AdminToolbar';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';
import { storageUsedPercent } from '../../utils/adminStorageMeter';
import { ProgressBar } from './ProgressBar';
import { GettingStartedChecklist } from '../dashboard/GettingStartedChecklist';

interface ContentStats {
  totalPages: number;
  totalArticles: number;
  totalMedia: number;
  totalUsers: number;
  totalBackups: number;
  recentActivity: Array<Record<string, unknown>>;
}

interface DashboardData {
  stats: ContentStats;
  overview: DashboardOverview | null;
  apm: ApmOverview | null;
}

function formatStorageQuotaLabel(bytes: number): string {
  if (bytes >= 1024 ** 3) {
    return `${Math.round(bytes / 1024 ** 3)} GB`;
  }

  if (bytes >= 1024 ** 2) {
    return `${Math.round(bytes / 1024 ** 2)} MB`;
  }

  return `${bytes} B`;
}

export const DashboardView: React.FC = () => {
  const { get } = useApi();
  const toast = useToast();
  const { t } = useI18n();
  const { settings } = useSettings();
  const isDemoInstance = settings.demo?.enabled === true;

  const { data, isLoading, isFetching, refetch } = useAdminListQuery<DashboardData>({
    queryKey: queryKeys.dashboard.stats,
    queryFn: async () => {
      try {
        const [pagesRes, articlesRes, mediaRes, usersRes, backupsRes, auditRes, monitoring, apm] =
          await Promise.all([
            get('/api/pages'),
            get('/api/articles'),
            get('/api/media'),
            get<{ users: Array<{ id: string }> }>('/api/admin/users'),
            get<unknown[]>('/api/admin/backups'),
            get<{ recent_events?: Array<Record<string, unknown>> }>('/api/admin/audit/stats'),
            getDashboardOverview(),
            getApmOverview(),
          ]);

        return {
          stats: {
            totalPages: pagesRes.success ? (Array.isArray(pagesRes.data) ? pagesRes.data.length : 0) : 0,
            totalArticles: articlesRes.success
              ? Array.isArray(articlesRes.data)
                ? articlesRes.data.length
                : 0
              : 0,
            totalMedia: mediaRes.success ? (Array.isArray(mediaRes.data) ? mediaRes.data.length : 0) : 0,
            totalUsers:
              usersRes.success && usersRes.data?.users ? usersRes.data.users.length : 0,
            totalBackups: backupsRes.success
              ? Array.isArray(backupsRes.data)
                ? backupsRes.data.length
                : 0
              : 0,
            recentActivity: auditRes.success ? auditRes.data?.recent_events || [] : [],
          },
          overview: monitoring,
          apm,
        };
      } catch (error) {
        toast.error(t('dashboard.toast.loadFailed'));
        console.error(error);
        throw error;
      }
    },
  });

  const loading = isLoading && !data;
  const stats = data?.stats ?? {
    totalPages: 0,
    totalArticles: 0,
    totalMedia: 0,
    totalUsers: 0,
    totalBackups: 0,
    recentActivity: [],
  };
  const overview = data?.overview ?? null;
  const apm = data?.apm ?? null;

  const analytics = overview?.analytics;
  const counts = overview?.counts;
  const storageFree = overview?.storage?.free_space;
  const demoStorageQuota =
    overview?.storage?.demo_synthetic && overview.storage.demo_quota_bytes
      ? formatStorageQuotaLabel(overview.storage.demo_quota_bytes)
      : null;
  const contentStorage = overview?.storage?.content;

  if (loading) {
    return <AdminPageSkeleton />;
  }

  const kpiCards = [
    { id: 'pages', title: t('dashboard.kpi.pages'), value: stats.totalPages, icon: FileText, to: '/pages' },
    { id: 'articles', title: t('dashboard.kpi.articles'), value: stats.totalArticles, icon: BookOpen, to: '/articles' },
    { id: 'media', title: t('dashboard.kpi.media'), value: counts?.media ?? stats.totalMedia, icon: ImageIcon, to: '/media' },
    {
      id: 'visits',
      title: t('dashboard.kpi.visitsToday'),
      value: analytics?.overview.visits ?? 0,
      icon: ArrowUpRight,
      to: '/analytics',
    },
    { id: 'users', title: t('dashboard.kpi.users'), value: stats.totalUsers, icon: Users, to: '/users' },
  ];
  const usedPercent = storageUsedPercent(overview?.storage);

  return (
    <div className="space-y-6 animate-fadeIn pb-16">
      <SystemUpdateBanner />

      {stats.totalPages === 0 && stats.totalArticles === 0 ? (
        <AdminEmptyState
          title={t('dashboard.empty.title')}
          description={t('dashboard.empty.body')}
          action={
            <div className="flex flex-wrap justify-center gap-2">
              <Link to="/pages/new" className="btn btn-primary">
                {t('dashboard.empty.createPage')}
              </Link>
              <Link to="/articles/new" className="btn btn-secondary">
                {t('dashboard.empty.createArticle')}
              </Link>
            </div>
          }
        />
      ) : null}

      <AdminToolbar>
        <div className="min-w-0">
          <p className="text-[11px] font-bold uppercase tracking-wider text-admin-primary">{t('dashboard.hero.badge')}</p>
          <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-admin-text mt-1">
            {t('dashboard.hero.title')}
          </h2>
          <p className="mt-1 text-sm text-admin-muted max-w-2xl">{t('dashboard.hero.subtitle')}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link
            to="/articles"
            className="bg-admin-primary hover:bg-admin-primary-hover text-white font-bold px-5 py-2.5 rounded-lg transition-all flex items-center gap-2 text-sm"
          >
            <PlusCircle className="w-4 h-4" />
            <span>{t('dashboard.hero.newPost')}</span>
          </Link>
          <button
            type="button"
            onClick={() => void refetch()}
            disabled={isFetching}
            className="bg-admin-card hover:bg-admin-sidebar-hover text-admin-text font-semibold px-4 py-2.5 rounded-lg border border-admin-border transition-all text-sm disabled:opacity-60"
          >
            {isFetching ? t('dashboard.hero.refreshing') : t('dashboard.hero.refresh')}
          </button>
        </div>
      </AdminToolbar>

      <GettingStartedChecklist
        totalPages={stats.totalPages}
        totalArticles={stats.totalArticles}
        totalMedia={counts?.media ?? stats.totalMedia}
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5 gap-4">
        {kpiCards.map((card) => (
          <AdminKpiCard
            key={card.id}
            title={card.title}
            value={card.value}
            icon={card.icon}
            to={card.to}
          />
        ))}
      </div>

      <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <AdminWidgetCard
          className="xl:col-span-2"
          title={t('dashboard.chart.title')}
          action={
            <Link to="/analytics" className="text-sm font-semibold text-admin-primary hover:underline">
              {t('dashboard.chart.analyticsLink')}
            </Link>
          }
        >
          <AnalyticsChart data={analytics?.chart ?? []} loading={false} />
        </AdminWidgetCard>
        <div className="space-y-4">
          <ProjectPlannerSummaryWidget />
          <AdminWidgetCard title={t('dashboard.storage.title')}>
            <div className="flex items-start justify-between gap-3 mb-3">
              <div>
                <p className="text-2xl font-bold text-admin-text">
                  {isDemoInstance ? t('dashboard.stats.demoStorageFree') : t('dashboard.stats.diskFree')}
                </p>
                <p className="text-sm text-admin-muted mt-1">{storageFree ?? '—'}</p>
                {isDemoInstance && demoStorageQuota ? (
                  <p className="mt-1 text-xs text-admin-muted">
                    {t('dashboard.stats.demoStorageQuota', { quota: demoStorageQuota })}
                  </p>
                ) : null}
              </div>
              <HardDrive className="w-8 h-8 text-admin-primary shrink-0" />
            </div>
            {usedPercent !== null ? (
              <>
                <ProgressBar percent={usedPercent} tone="indigo" />
                <p className="mt-2 text-xs font-semibold text-admin-muted">
                  {t('dashboard.storage.used', { percent: usedPercent })}
                </p>
              </>
            ) : (
              <p className="text-xs text-admin-muted">{t('dashboard.storage.free', { free: storageFree ?? '—' })}</p>
            )}
          </AdminWidgetCard>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <Link to="/messages">
          <AdminKpiCard
            title={t('dashboard.stats.unreadMessages')}
            value={counts?.messages_unread ?? 0}
            icon={Mail}
          />
        </Link>
        <Link to="/pages?stale=1">
          <AdminKpiCard
            title={t('dashboard.stats.staleContent')}
            value={counts?.stale_content ?? 0}
            icon={Clock3}
          />
        </Link>
        <AdminKpiCard title={t('dashboard.kpi.backups')} value={stats.totalBackups} icon={HardDrive} to="/backups" />
        <AdminKpiCard
          title={t('dashboard.stats.realtimeVisitors')}
          value={analytics?.realtime.active_visitors ?? 0}
          icon={ArrowUpRight}
          to="/analytics"
        />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <AdminKpiCard title={t('dashboard.stats.activeLocks')} value={overview?.locks_count ?? 0} icon={HardDrive} />
        <AdminKpiCard title={t('dashboard.stats.conflicts')} value={overview?.conflicts_count ?? 0} icon={Settings} />
        <AdminKpiCard
          title={t('dashboard.stats.systemStatus')}
          value={overview?.health?.status ?? 'unknown'}
          icon={Sparkles}
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <HealthPanel health={overview?.health ?? null} loading={false} />
        <PerformanceGuardPanel overview={apm} loading={false} onRefresh={() => void refetch()} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <LocksPanel
          locks={overview?.locks ?? []}
          loading={false}
          onRefresh={() => void refetch()}
        />
        <ConflictsPanel
          conflicts={overview?.conflicts ?? []}
          totalCount={overview?.conflicts_count ?? 0}
          loading={false}
        />
      </div>

      <LogsPanel
        bySeverity={overview?.logs?.by_severity ?? {}}
        hours={overview?.logs?.hours ?? 24}
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {[
          { to: '/pages', label: t('dashboard.quickLinks.pages'), icon: FileText },
          { to: '/articles', label: t('dashboard.quickLinks.articles'), icon: BookOpen },
          { to: '/users', label: t('dashboard.quickLinks.users'), icon: Users },
          { to: '/settings', label: t('dashboard.quickLinks.settings'), icon: Settings },
        ].map((link) => {
          const Icon = link.icon;
          return (
            <Link
              key={link.to}
              to={link.to}
              className="bg-admin-card rounded-lg border border-admin-border shadow-admin p-5 hover:border-admin-primary transition-all group"
            >
              <div className="flex items-center gap-3">
                <div className="rounded-lg bg-admin-sidebar-active p-3 text-admin-primary group-hover:scale-105 transition-transform">
                  <Icon className="w-5 h-5" />
                </div>
                <span className="font-semibold text-admin-text">{link.label}</span>
              </div>
            </Link>
          );
        })}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2">
          <DashboardActivityPanel events={stats.recentActivity} loading={false} />
        </div>
        <DashboardDiskStructurePanel
          pages={contentStorage?.pages ?? stats.totalPages}
          articles={contentStorage?.articles ?? stats.totalArticles}
          media={contentStorage?.media ?? stats.totalMedia}
          users={contentStorage?.users ?? stats.totalUsers}
          totalHuman={contentStorage?.total_human}
          documentCount={contentStorage?.document_count}
          loading={false}
        />
      </div>
    </div>
  );
};

export default DashboardView;
