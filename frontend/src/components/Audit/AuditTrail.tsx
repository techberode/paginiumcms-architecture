// frontend/src/components/Audit/AuditTrail.tsx
import React, { useState, useEffect } from 'react';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { formatDistanceToNow, format } from 'date-fns';
import { sk } from 'date-fns/locale';

interface AuditTrailProps {
  contentId?: string;
  userId?: string;
}

export const AuditTrail: React.FC<AuditTrailProps> = ({ contentId, userId }) => {
  const [events, setEvents] = useState<any[]>([]);
  const [stats, setStats] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [showStats, setShowStats] = useState(false);
  const { get } = useApi();
  const toast = useToast();

  useEffect(() => {
    if (contentId) {
      loadContentAudit();
    } else if (userId) {
      loadUserAudit();
    } else {
      loadStats();
    }
  }, [contentId, userId]);

  const loadContentAudit = async () => {
    setLoading(true);
    try {
      const response = await get<any>(`/api/admin/audit/content/${contentId}`);
      if (response.success) {
        setEvents(response.data?.events || []);
        setStats(response.data?.stats);
      }
    } catch (error) {
      toast.error('Failed to load audit trail');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const loadUserAudit = async () => {
    setLoading(true);
    try {
      const response = await get<any>(`/api/admin/audit/user/${userId}`);
      if (response.success) {
        setEvents(response.data?.events || []);
      }
    } catch (error) {
      toast.error('Failed to load user audit');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const loadStats = async () => {
    setLoading(true);
    try {
      const response = await get<any>('/api/admin/audit/stats');
      if (response.success) {
        setStats(response.data);
        setEvents(response.data?.recent_events || []);
      }
    } catch (error) {
      toast.error('Failed to load audit stats');
      console.error(error);
    } finally {
      setLoading(false);
    }
  };

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'CRITICAL': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
      case 'ERROR': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200';
      case 'WARNING': return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
      default: return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
    }
  };

  const getActionIcon = (action: string) => {
    const icons: Record<string, string> = {
      'create': '➕',
      'update': '✏️',
      'delete': '🗑️',
      'restore': '↩️',
      'read': '👁️',
      'login': '🔑',
      'logout': '🚪',
      'backup': '💾',
      'restore_backup': '📥',
    };
    return icons[action] || '📌';
  };

  const handleExport = async () => {
    try {
      const response = await get<any>('/api/admin/audit/export', {
        responseType: 'blob',
      });
      if (response.success && response.data) {
        const blob = response.data as Blob;
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit_trail_${format(new Date(), 'yyyy-MM-dd')}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        toast.success('Audit exported successfully');
      }
    } catch (error) {
      toast.error('Failed to export audit');
      console.error(error);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>
    );
  }

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
      <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center flex-wrap gap-2">
        <h3 className="text-lg font-semibold">
          {contentId ? 'Content Audit Trail' : userId ? 'User Audit Trail' : 'Audit Trail'}
        </h3>
        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowStats(!showStats)}
            className="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
          >
            {showStats ? 'Hide Stats' : 'Show Stats'}
          </button>
          <button
            onClick={handleExport}
            className="px-3 py-1 text-sm bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-800"
          >
            Export CSV
          </button>
        </div>
      </div>

      {/* Stats */}
      {showStats && stats && (
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">Total Events</div>
              <div className="text-2xl font-bold">{stats.total_events || 0}</div>
            </div>
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">Categories</div>
              <div className="text-2xl font-bold">{Object.keys(stats.by_category || {}).length}</div>
            </div>
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">Unique Users</div>
              <div className="text-2xl font-bold">{Object.keys(stats.by_user || {}).length}</div>
            </div>
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">Errors</div>
              <div className="text-2xl font-bold text-red-600">
                {(stats.by_severity?.ERROR || 0) + (stats.by_severity?.CRITICAL || 0)}
              </div>
            </div>
          </div>

          {/* Timeline */}
          {stats.timeline && (
            <div className="mt-4">
              <div className="text-sm font-medium mb-2">Activity Timeline (Last 7 Days)</div>
              <div className="flex items-end h-16 gap-1">
                {Object.entries(stats.timeline).slice(-7).map(([date, count]) => (
                  <div key={date} className="flex-1 flex flex-col items-center">
                    <div 
                      className="w-full bg-indigo-500 dark:bg-indigo-400 rounded-t"
                      style={{ height: `${Math.min((count / 10) * 100, 100)}%` }}
                    />
                    <span className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {format(new Date(date), 'dd.MM')}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* Events list */}
      <div className="p-4">
        <div className="space-y-2 max-h-96 overflow-y-auto">
          {events.length === 0 ? (
            <p className="text-gray-500 dark:text-gray-400 text-center py-8">
              No audit events found
            </p>
          ) : (
            events.map((event, index) => (
              <div
                key={index}
                className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              >
                <span className="text-xl mt-0.5">{getActionIcon(event.log?.context?.action)}</span>
                
                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-medium text-sm">
                      {event.log?.context?.action || 'Unknown'}
                    </span>
                    <span className={`px-2 py-0.5 text-xs rounded-full ${getSeverityColor(event.log?.severity || 'INFO')}`}>
                      {event.log?.severity || 'INFO'}
                    </span>
                    {event.version && (
                      <span className="px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 rounded-full">
                        v{event.version.version}
                      </span>
                    )}
                  </div>
                  
                  <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {event.log?.message || 'No message'}
                  </p>
                  
                  <div className="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>
                      {event.user?.name || event.log?.context?.user?.name || 'System'}
                    </span>
                    <span>
                      {event.user?.email || event.log?.context?.user?.email || ''}
                    </span>
                    <span>
                      {formatDistanceToNow(new Date(event.timestamp), { addSuffix: true, locale: sk })}
                    </span>
                    {event.version && (
                      <span>
                        Size: {event.version.content?.length || 0} chars
                      </span>
                    )}
                  </div>
                </div>

                <span className="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                  {format(new Date(event.timestamp), 'HH:mm:ss')}
                </span>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
};

export default AuditTrail;
