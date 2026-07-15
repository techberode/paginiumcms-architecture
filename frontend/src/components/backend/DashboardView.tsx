// frontend/src/components/backend/DashboardView.tsx
// === Admin dashboard (Iteration 7) ===
import React, { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
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

  const StatCard: React.FC<{
    title: string;
    value: number | string;
    icon: string;
    color: string;
  }> = ({ title, value, icon, color }) => (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-gray-500 dark:text-gray-400">{title}</p>
          <p className="text-2xl font-semibold text-gray-900 dark:text-white mt-1">
            {loading ? '...' : value}
          </p>
        </div>
        <div className={`p-3 rounded-full ${color}`}>
          <span className="text-xl">{icon}</span>
        </div>
      </div>
    </div>
  );

  const analytics = overview?.analytics;

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <button onClick={() => void loadDashboardData()} className="btn btn-secondary text-sm" type="button">
          Refresh
        </button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard title="Pages" value={stats.totalPages} icon="📄" color="bg-blue-100 dark:bg-blue-900/30" />
        <StatCard title="Articles" value={stats.totalArticles} icon="✍️" color="bg-green-100 dark:bg-green-900/30" />
        <StatCard title="Users" value={stats.totalUsers} icon="👤" color="bg-purple-100 dark:bg-purple-900/30" />
        <StatCard title="Backups" value={stats.totalBackups} icon="💾" color="bg-orange-100 dark:bg-orange-900/30" />
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Visits today"
          value={analytics?.overview.visits ?? 0}
          icon="📈"
          color="bg-indigo-100 dark:bg-indigo-900/30"
        />
        <StatCard
          title="Realtime visitors"
          value={analytics?.realtime.active_visitors ?? 0}
          icon="👁️"
          color="bg-cyan-100 dark:bg-cyan-900/30"
        />
        <StatCard
          title="Active locks"
          value={overview?.locks_count ?? 0}
          icon="🔒"
          color="bg-amber-100 dark:bg-amber-900/30"
        />
        <StatCard
          title="Conflicts"
          value={overview?.conflicts_count ?? 0}
          icon="⚠️"
          color="bg-red-100 dark:bg-red-900/30"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <HealthPanel health={overview?.health ?? null} loading={loading} />
        <div className="card">
          <div className="card-body">
            <div className="flex items-center justify-between mb-3">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Visits (14 days)</h2>
              <Link to="/notifications" className="text-sm text-indigo-600 hover:underline">
                Full analytics
              </Link>
            </div>
            <AnalyticsChart data={analytics?.chart ?? []} loading={loading} />
          </div>
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

      <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Recent activity</h2>
        </div>
        <div className="p-4">
          {loading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
            </div>
          ) : stats.recentActivity.length === 0 ? (
            <p className="text-center text-gray-500 dark:text-gray-400 py-8">No recent activity</p>
          ) : (
            <div className="divide-y divide-gray-100 dark:divide-gray-700">
              {stats.recentActivity.slice(0, 10).map((event, index) => (
                <div key={index} className="py-2 text-sm text-gray-700 dark:text-gray-200">
                  {(event as { log?: { message?: string } }).log?.message || 'Unknown action'}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Link
          to="/pages"
          className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition-shadow text-center"
        >
          <span className="text-3xl block mb-2">📄</span>
          <h3 className="font-medium text-gray-900 dark:text-white">Manage pages</h3>
        </Link>
        <Link
          to="/code-editor"
          className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition-shadow text-center"
        >
          <span className="text-3xl block mb-2">💻</span>
          <h3 className="font-medium text-gray-900 dark:text-white">Code editor</h3>
        </Link>
        <Link
          to="/notifications"
          className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition-shadow text-center"
        >
          <span className="text-3xl block mb-2">🔔</span>
          <h3 className="font-medium text-gray-900 dark:text-white">Notifications</h3>
        </Link>
      </div>
    </div>
  );
};

export default DashboardView;
