// frontend/src/components/Audit/AuditTrail.tsx
import React, { useState, useEffect, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { useApi } from '../../hooks/useApi';
import { useToast } from '../../hooks/useToast';
import { format } from 'date-fns';
import { formatAuditEventActor, formatAuditEventMessage } from '../../utils/formatAuditEvent';
import { formatDisplayClockTime, formatDisplayShortDate, formatRelativeTime } from '../../utils/contentDates';
import type { AuditEvent, AuditStats } from '../../api/types';
import { useI18n } from '../../context/I18nContext';

interface AuditTrailProps {
  contentId?: string;
  userId?: string;
}

export const AuditTrail: React.FC<AuditTrailProps> = ({ contentId: contentIdProp, userId: userIdProp }) => {
  const { t, locale } = useI18n();
  const { contentId: routeContentId, userId: routeUserId } = useParams<{
    contentId?: string;
    userId?: string;
  }>();
  const contentId = contentIdProp ?? routeContentId;
  const userId = userIdProp ?? routeUserId;
  const [events, setEvents] = useState<AuditEvent[]>([]);
  const [stats, setStats] = useState<AuditStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [showStats, setShowStats] = useState(false);
  const { get } = useApi();
  const toast = useToast();

  const loadContentAudit = useCallback(async () => {
    setLoading(true);
    try {
      const response = await get<{ events?: AuditEvent[]; stats?: AuditStats }>(`/api/admin/audit/content/${contentId}`);
      if (response.success) {
        setEvents(response.data?.events || []);
        setStats(response.data?.stats ?? null);
      }
    } catch (error) {
      toast.error(t('platform.auditTrail.toast.loadContentFailed'));
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [contentId, get, toast, t]);

  const loadUserAudit = useCallback(async () => {
    setLoading(true);
    try {
      const response = await get<{ events?: AuditEvent[] }>(`/api/admin/audit/user/${userId}`);
      if (response.success) {
        setEvents(response.data?.events || []);
      }
    } catch (error) {
      toast.error(t('platform.auditTrail.toast.loadUserFailed'));
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [userId, get, toast, t]);

  const loadStats = useCallback(async () => {
    setLoading(true);
    try {
      const response = await get<AuditStats>('/api/admin/audit/stats');
      if (response.success) {
        setStats(response.data ?? null);
        setEvents(response.data?.recent_events || []);
      }
    } catch (error) {
      toast.error(t('platform.auditTrail.toast.loadStatsFailed'));
      console.error(error);
    } finally {
      setLoading(false);
    }
  }, [get, toast, t]);

  useEffect(() => {
    if (contentId) {
      void loadContentAudit();
    } else if (userId) {
      void loadUserAudit();
    } else {
      void loadStats();
    }
  }, [contentId, userId, loadContentAudit, loadUserAudit, loadStats]);

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
      const response = await get<Blob>('/api/admin/audit/export', {
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
        toast.success(t('platform.auditTrail.toast.exportSuccess'));
      }
    } catch (error) {
      toast.error(t('platform.auditTrail.toast.exportFailed'));
      console.error(error);
    }
  };

  const title = contentId
    ? t('platform.auditTrail.contentTitle')
    : userId
      ? t('platform.auditTrail.userTitle')
      : t('platform.auditTrail.title');

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
        <h3 className="text-lg font-semibold">{title}</h3>
        <div className="flex items-center gap-2">
          <button
            onClick={() => setShowStats(!showStats)}
            className="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
          >
            {showStats ? t('platform.auditTrail.hideStats') : t('platform.auditTrail.showStats')}
          </button>
          <button
            onClick={handleExport}
            className="px-3 py-1 text-sm bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-800"
          >
            {t('platform.auditTrail.exportCsv')}
          </button>
        </div>
      </div>

      {showStats && stats && (
        <div className="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">{t('platform.auditTrail.totalEvents')}</div>
              <div className="text-2xl font-bold">{stats.total_events || 0}</div>
            </div>
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">{t('platform.auditTrail.categories')}</div>
              <div className="text-2xl font-bold">{Object.keys(stats.by_category || {}).length}</div>
            </div>
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">{t('platform.auditTrail.uniqueUsers')}</div>
              <div className="text-2xl font-bold">{Object.keys(stats.by_user || {}).length}</div>
            </div>
            <div>
              <div className="text-sm text-gray-500 dark:text-gray-400">{t('platform.auditTrail.errors')}</div>
              <div className="text-2xl font-bold text-red-600">
                {(stats.by_severity?.ERROR || 0) + (stats.by_severity?.CRITICAL || 0)}
              </div>
            </div>
          </div>

          {stats.timeline && (
            <div className="mt-4">
              <div className="text-sm font-medium mb-2">{t('platform.auditTrail.timeline')}</div>
              <div className="flex items-end h-16 gap-1">
                {Object.entries(stats.timeline).slice(-7).map(([date, count]) => (
                  <div key={date} className="flex-1 flex flex-col items-center">
                    <div
                      className="w-full bg-indigo-500 dark:bg-indigo-400 rounded-t"
                      style={{ height: `${Math.min((Number(count) / 10) * 100, 100)}%` }}
                    />
                    <span className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                      {formatDisplayShortDate(date, locale)}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      <div className="p-4">
        <div className="space-y-2 max-h-96 overflow-y-auto">
          {events.length === 0 ? (
            <p className="text-gray-500 dark:text-gray-400 text-center py-8">{t('platform.auditTrail.empty')}</p>
          ) : (
            events.map((event, index) => {
              const logRecord = (event.log ?? event) as Record<string, unknown>;
              const displayMessage = formatAuditEventMessage(logRecord, locale);
              const displayActor = formatAuditEventActor(logRecord, locale);
              const action = (logRecord.context as { action?: string } | undefined)?.action ?? 'unknown';

              return (
              <div
                key={index}
                className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
              >
                <span className="text-xl mt-0.5">{getActionIcon(action)}</span>

                <div className="flex-1 min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <span className="font-medium text-sm capitalize">
                      {action}
                    </span>
                    <span className={`px-2 py-0.5 text-xs rounded-full ${getSeverityColor((logRecord.severity as string) || 'INFO')}`}>
                      {(logRecord.severity as string) || 'INFO'}
                    </span>
                    {event.version && (
                      <span className="px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-400 rounded-full">
                        v{event.version.version}
                      </span>
                    )}
                  </div>

                  <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {displayMessage}
                  </p>

                  <div className="flex items-center gap-4 mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>
                      {displayActor}
                    </span>
                    <span>
                      {formatRelativeTime(event.timestamp, locale)}
                    </span>
                    {event.version && (
                      <span>
                        {t('platform.auditTrail.sizeChars', {
                          count: event.version.content?.length || 0,
                        })}
                      </span>
                    )}
                  </div>
                </div>

                <span className="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                  {formatDisplayClockTime(event.timestamp, locale)}
                </span>
              </div>
            );
            })
          )}
        </div>
      </div>
    </div>
  );
};

export default AuditTrail;
