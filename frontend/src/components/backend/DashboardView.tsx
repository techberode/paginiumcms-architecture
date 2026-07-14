// frontend/src/components/backend/DashboardView.tsx
import React, { useState, useEffect } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';

interface DashboardStats {
  totalPages: number;
  totalArticles: number;
  totalUsers: number;
  totalBackups: number;
  recentActivity: any[];
}

export const DashboardView: React.FC = () => {
  const [stats, setStats] = useState<DashboardStats>({
    totalPages: 0,
    totalArticles: 0,
    totalUsers: 0,
    totalBackups: 0,
    recentActivity: [],
  });
  const [loading, setLoading] = useState(true);
  const { get } = useApi();
  const toast = useToast();

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      // Načítanie štatistík - použijeme existujúce API endpointy
      const [pagesRes, articlesRes, usersRes, backupsRes, auditRes] = await Promise.all([
        get('/api/pages'),
        get('/api/articles'),
        get('/api/admin/users'),
        get('/api/admin/backups'),
        get('/api/admin/audit/stats'),
      ]);

      setStats({
        totalPages: pagesRes.success ? (pagesRes.data?.length || 0) : 0,
        totalArticles: articlesRes.success ? (articlesRes.data?.length || 0) : 0,
        totalUsers: usersRes.success ? (usersRes.data?.length || 0) : 0,
        totalBackups: backupsRes.success ? (backupsRes.data?.length || 0) : 0,
        recentActivity: auditRes.success ? (auditRes.data?.recent_events || []) : [],
      });
    } catch (error) {
      toast.error('Failed to load dashboard data');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const StatCard: React.FC<{
    title: string;
    value: number;
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

  const ActivityItem: React.FC<{ event: any }> = ({ event }) => {
    const getActionColor = (action: string) => {
      const colors: Record<string, string> = {
        'create': 'text-green-600 dark:text-green-400',
        'update': 'text-blue-600 dark:text-blue-400',
        'delete': 'text-red-600 dark:text-red-400',
        'restore': 'text-purple-600 dark:text-purple-400',
        'read': 'text-gray-600 dark:text-gray-400',
      };
      return colors[action] || 'text-gray-600 dark:text-gray-400';
    };

    return (
      <div className="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
        <div className="flex items-center gap-3">
          <span className="text-lg">{event.log?.context?.action || '📌'}</span>
          <div>
            <p className="text-sm text-gray-900 dark:text-white">
              {event.log?.message || 'Unknown action'}
            </p>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              {event.user?.name || event.log?.context?.user?.name || 'System'} • 
              {new Date(event.timestamp).toLocaleString()}
            </p>
          </div>
        </div>
        <span className={`text-xs font-medium ${getActionColor(event.log?.context?.action)}`}>
          {event.log?.context?.action || 'unknown'}
        </span>
      </div>
    );
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
        <button
          onClick={loadDashboardData}
          className="btn btn-secondary text-sm"
        >
          🔄 Refresh
        </button>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Pages"
          value={stats.totalPages}
          icon="📄"
          color="bg-blue-100 dark:bg-blue-900/30"
        />
        <StatCard
          title="Articles"
          value={stats.totalArticles}
          icon="✍️"
          color="bg-green-100 dark:bg-green-900/30"
        />
        <StatCard
          title="Users"
          value={stats.totalUsers}
          icon="👤"
          color="bg-purple-100 dark:bg-purple-900/30"
        />
        <StatCard
          title="Backups"
          value={stats.totalBackups}
          icon="💾"
          color="bg-orange-100 dark:bg-orange-900/30"
        />
      </div>

      {/* Recent Activity */}
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            Recent Activity
          </h2>
        </div>
        <div className="p-4">
          {loading ? (
            <div className="flex justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
          ) : stats.recentActivity.length === 0 ? (
            <p className="text-center text-gray-500 dark:text-gray-400 py-8">
              No recent activity
            </p>
          ) : (
            <div className="divide-y divide-gray-100 dark:divide-gray-700">
              {stats.recentActivity.slice(0, 10).map((event, index) => (
                <ActivityItem key={index} event={event} />
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a
          href="/pages"
          className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition-shadow text-center"
        >
          <span className="text-3xl block mb-2">📄</span>
          <h3 className="font-medium text-gray-900 dark:text-white">Manage Pages</h3>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Create and edit pages
          </p>
        </a>
        <a
          href="/code-editor"
          className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition-shadow text-center"
        >
          <span className="text-3xl block mb-2">💻</span>
          <h3 className="font-medium text-gray-900 dark:text-white">Code Editor</h3>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Edit source code files
          </p>
        </a>
        <a
          href="/backups"
          className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 hover:shadow-md transition-shadow text-center"
        >
          <span className="text-3xl block mb-2">💾</span>
          <h3 className="font-medium text-gray-900 dark:text-white">Backup</h3>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Create and restore backups
          </p>
        </a>
      </div>
    </div>
  );
};

export default DashboardView;
