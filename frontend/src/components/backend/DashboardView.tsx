// frontend/src/components/backend/DashboardView.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  FileText,
  BookOpen,
  HardDrive,
  Users,
  PlusCircle,
  Sparkles,
  ArrowUpRight,
} from 'lucide-react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { getDashboardOverview, DashboardOverview } from '../../api/dashboard';
import { AnalyticsChart } from '../dashboard/AnalyticsChart';
import { LocksPanel } from '../dashboard/LocksPanel';
import { ConflictsPanel } from '../dashboard/ConflictsPanel';
import { HealthPanel } from '../dashboard/HealthPanel';

interface ContentStats {
  totalPages: number;
  totalArticles: number;
  totalUsers: number;
  totalBackups: number;
  recentActivity: Array<Record<string, unknown>>;
}

export const DashboardView: React.FC = () => {
  const [stats, setStats] = useState<ContentStats>({
    totalPages: 0,
    totalArticles: 0,
    totalUsers: 0,
    totalBackups: 0,
    recentActivity: [],
  });
  const [overview, setOverview] = useState<DashboardOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const { get } = useApi();
  const toast = useToast();

  const loadDashboardData = useCallback(async () => {
    setLoading(true);
    try {
      const [pagesRes, articlesRes, usersRes, backupsRes, auditRes, monitoring] = await Promise.all([
        get('/api/pages'),
        get('/api/articles'),
        get('/api/admin/users'),
        get('/api/admin/backups'),
        get('/api/admin/audit/stats'),
        getDashboardOverview(),
      ]);

      setStats({
        totalPages: pagesRes.success ? (pagesRes.data?.length || 0) : 0,
        totalArticles: articlesRes.success ? (articlesRes.data?.length || 0) : 0,
        totalUsers: usersRes.success ? (usersRes.data?.length || 0) : 0,
        totalBackups: backupsRes.success ? (backupsRes.data?.length || 0) : 0,
        recentActivity: auditRes.success ? (auditRes.data?.recent_events || []) : [],
      });
      setOverview(monitoring);
    } catch (error) {
      toast.error('Failed to load dashboard data');
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [get, toast]);

  useEffect(() => {
    void loadDashboardData();
  }, [loadDashboardData]);

  const analytics = overview?.analytics;

  const kpiCards = [
    { title: 'Stránky', value: stats.totalPages, icon: FileText, to: '/pages', color: 'indigo' },
    { title: 'Články', value: stats.totalArticles, icon: BookOpen, to: '/articles', color: 'emerald' },
    { title: 'Používatelia', value: stats.totalUsers, icon: Users, to: '/users', color: 'violet' },
    { title: 'Zálohy', value: stats.totalBackups, icon: HardDrive, to: '/backups', color: 'amber' },
    {
      title: 'Návštevy dnes',
      value: analytics?.overview.visits ?? 0,
      icon: ArrowUpRight,
      to: '/notifications',
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
              <span>PaginiumCMS • FlatFile Architektúra</span>
            </div>
            <h2 className="text-3xl sm:text-4xl font-black tracking-tight leading-tight">
              Vitajte v Riadiacom Centre
            </h2>
            <p className="mt-3 text-indigo-100 text-sm sm:text-base leading-relaxed">
              Monitoring, zdravie systému a správa obsahu z jedného miesta.
            </p>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Link
              to="/articles"
              className="bg-indigo-600 hover:bg-indigo-500 text-white font-extrabold px-6 py-3.5 rounded-2xl shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-2 text-sm"
            >
              <PlusCircle className="w-4 h-4" />
              <span>Nový príspevok</span>
            </Link>
            <button
              type="button"
              onClick={() => void loadDashboardData()}
              className="bg-slate-800/80 hover:bg-slate-800 text-slate-200 font-bold px-5 py-3.5 rounded-2xl border border-slate-700 transition-all text-sm"
            >
              Obnoviť dáta
            </button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6">
        {kpiCards.map((card) => {
          const Icon = card.icon;
          return (
            <Link
              key={card.title}
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
                  {loading ? '…' : card.value}
                </div>
                <div className="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mt-1">
                  {card.title}
                </div>
              </div>
            </Link>
          );
        })}
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">Realtime návštevníci</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
            {loading ? '…' : (analytics?.realtime.active_visitors ?? 0)}
          </p>
        </div>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">Aktívne zámky</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
            {loading ? '…' : (overview?.locks_count ?? 0)}
          </p>
        </div>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">Konflikty</p>
          <p className="text-2xl font-black text-slate-900 dark:text-white mt-1">
            {loading ? '…' : (overview?.conflicts_count ?? 0)}
          </p>
        </div>
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
          <p className="text-xs font-bold uppercase text-slate-500">Stav systému</p>
          <p className="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 capitalize">
            {loading ? '…' : (overview?.health?.status ?? 'unknown')}
          </p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <HealthPanel health={overview?.health ?? null} loading={loading} />
        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
          <div className="flex items-center justify-between mb-3">
            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Návštevy (14 dní)</h2>
            <Link to="/notifications" className="text-sm text-indigo-600 hover:underline">
              Analytika
            </Link>
          </div>
          <AnalyticsChart data={analytics?.chart ?? []} loading={loading} />
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <LocksPanel
          locks={overview?.locks ?? []}
          loading={loading}
          onRefresh={() => void loadDashboardData()}
        />
        <ConflictsPanel
          conflicts={overview?.conflicts ?? []}
          totalCount={overview?.conflicts_count ?? 0}
          loading={loading}
        />
      </div>

      <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
        <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800">
          <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Posledná aktivita</h2>
        </div>
        <div className="p-6">
          {loading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
            </div>
          ) : stats.recentActivity.length === 0 ? (
            <p className="text-center text-slate-500 py-8">Žiadna nedávna aktivita</p>
          ) : (
            <div className="divide-y divide-slate-100 dark:divide-slate-800">
              {stats.recentActivity.slice(0, 10).map((event, index) => (
                <div key={index} className="py-2 text-sm text-slate-700 dark:text-slate-200">
                  {(event as { log?: { message?: string } }).log?.message || 'Unknown action'}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default DashboardView;
