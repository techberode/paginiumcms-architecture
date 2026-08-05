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
  Database,
  Settings,
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
import { AdminPageSkeleton } from '../ui/AdminPageSkeleton';
import { useI18n } from '../../context/I18nContext';
import { useSettings } from '../../hooks/useSettings';

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
    { id: 'pages', title: t('dashboard.kpi.pages'), value: stats.totalPages, icon: FileText, to: '/pages', color: 'indigo' },
    { id: 'articles', title: t('dashboard.kpi.articles'), value: stats.totalArticles, icon: BookOpen, to: '/articles', color: 'emerald' },
    { id: 'users', title: t('dashboard.kpi.users'), value: stats.totalUsers, icon: Users, to: '/users', color: 'violet' },
    { id: 'backups', title: t('dashboard.kpi.backups'), value: stats.totalBackups, icon: HardDrive, to: '/backups', color: 'amber' },
    {
      id: 'visits',
      title: t('dashboard.kpi.visitsToday'),
      value: analytics?.overview.visits ?? 0,
      icon: ArrowUpRight,
      to: '/analytics',
      color: 'cyan',
    },
  ];

  return (
    <div className="space-y-8 animate-fadeIn pb-16">
      <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-8 sm:p-10 text-white shadow-xl relative overflow-hidden border border-slate-800">
        <div className="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div className="max-w-xl">
            <div className="inline-flex items-center gap-2 bg-indigo-500/20 text-indigo-300 font-extrabold text-xs px-3 py-1 rounded-full mb-4 border border-indigo-500/30">
              <Sparkles className="w-3.5 h-3.5 text-indigo-400" />
              <span>{t('dashboard.hero.badge')}</span>
            </div>
            <h2 className="text-3xl sm:text-4xl font-black tracking-tight leading-tight">
              {t('dashboard.hero.title')}
            </h2>
            <p className="mt-3 text-indigo-100 text-sm sm:text-base leading-relaxed">
              {t('dashboard.hero.subtitle')}
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Link
              to="/articles"
              className="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-6 py-3.5 rounded-2xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 text-sm"
            >
              <PlusCircle className="w-4 h-4" />
              <span>{t('dashboard.hero.newPost')}</span>
            </Link>
            <button
              type="button"
              onClick={() => void refetch()}
              disabled={isFetching}
              className="bg-slate-800/80 hover:bg-slate-800 text-slate-200 font-bold px-5 py-3.5 rounded-2xl border border-slate-700 transition-all text-sm disabled:opacity-60"
            >
              {isFetching ? t('dashboard.hero.refreshing') : t('dashboard.hero.refresh')}
            </button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6">
        {kpiCards.map((card) => {
          const Icon = card.icon;
          return (
            <Link
              key={card.id}
              to={card.to}
              className="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-xl hover:border-indigo-500/50 transition-all group flex flex-col justify-between"
            >
              <div className="flex items-center justify-between mb-4">
                <div className="p-3 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                  <Icon className="w-6 h-6" />
                </div>
                <ArrowUpRight className="w-5 h-5 text-slate-300 dark:text-slate-600 group-hover:text-indigo-500 transition-colors" />
              </div>
              <div>
                <div className="text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                  {card.value}
                </div>
                <div className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">
                  {card.title}
                </div>
              </div>
            </Link>
          );
        })}
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Link
          to="/messages"
          className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 hover:border-indigo-500/50 transition-all group"
        >
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs font-bold uppercase text-slate-500">{t('dashboard.stats.unreadMessages')}</p>
              <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
                {counts?.messages_unread ?? 0}
              </p>
            </div>
            <Mail className="w-8 h-8 text-indigo-500 group-hover:scale-110 transition-transform" />
          </div>
        </Link>
        <Link
          to="/media"
          className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 hover:border-indigo-500/50 transition-all group"
        >
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs font-bold uppercase text-slate-500">{t('dashboard.stats.media')}</p>
              <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
                {counts?.media ?? stats.totalMedia}
              </p>
            </div>
            <ImageIcon className="w-8 h-8 text-emerald-500 group-hover:scale-110 transition-transform" />
          </div>
        </Link>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <div className="flex items-center justify-between gap-3">
            <div>
              <p className="text-xs font-bold uppercase text-slate-500">
                {isDemoInstance ? t('dashboard.stats.demoStorageFree') : t('dashboard.stats.diskFree')}
              </p>
              <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
                {storageFree ?? '—'}
              </p>
              {isDemoInstance && demoStorageQuota ? (
                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  {t('dashboard.stats.demoStorageQuota', { quota: demoStorageQuota })}
                </p>
              ) : null}
            </div>
            <Database className="w-8 h-8 text-amber-500" />
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">{t('dashboard.stats.realtimeVisitors')}</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
            {analytics?.realtime.active_visitors ?? 0}
          </p>
        </div>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">{t('dashboard.stats.activeLocks')}</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
            {overview?.locks_count ?? 0}
          </p>
        </div>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">{t('dashboard.stats.conflicts')}</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
            {overview?.conflicts_count ?? 0}
          </p>
        </div>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">{t('dashboard.stats.systemStatus')}</p>
          <p className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 capitalize">
            {overview?.health?.status ?? 'unknown'}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <HealthPanel health={overview?.health ?? null} loading={false} />
        <PerformanceGuardPanel overview={apm} loading={false} />
      </div>

      <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white">{t('dashboard.chart.title')}</h2>
          <Link to="/analytics" className="text-sm text-indigo-600 hover:underline">
            {t('dashboard.chart.analyticsLink')}
          </Link>
        </div>
        <AnalyticsChart data={analytics?.chart ?? []} loading={false} />
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
              className="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 p-5 hover:border-indigo-500/50 hover:shadow-lg transition-all group"
            >
              <div className="flex items-center gap-3">
                <div className="rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 p-3 text-indigo-600 group-hover:scale-110 transition-transform">
                  <Icon className="w-5 h-5" />
                </div>
                <span className="font-bold text-slate-900 dark:text-white">{link.label}</span>
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
