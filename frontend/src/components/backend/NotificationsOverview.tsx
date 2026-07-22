// frontend/src/components/backend/NotificationsOverview.tsx
// === Notifications & analytics overview (Iteration 6) ===
import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Settings } from 'lucide-react';
import {
  getNotificationOverview,
  sendTestNotification,
  testNotificationConnector,
  sendMonitoringReport,
  runMonitoringSchedule,
  NotificationOverview,
  type ConnectorStatus,
} from '../../api/notifications';
import { useToast } from '../../hooks/useToast';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { useI18n } from '../../context/I18nContext';

export const NotificationsOverview: React.FC = () => {
  const { t } = useI18n();
  const [data, setData] = useState<NotificationOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState<string | null>(null);
  const [testingAuth, setTestingAuth] = useState<string | null>(null);
  const [sendingReport, setSendingReport] = useState(false);
  const [runningSchedule, setRunningSchedule] = useState(false);
  const { success, error: toastError } = useToast();

  const load = async () => {
    setLoading(true);
    try {
      const overview = await getNotificationOverview();
      setData(overview);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void load();
  }, []);

  const handleTest = async (adapter: string) => {
    setTesting(adapter);
    try {
      const result = await sendTestNotification(adapter, data?.fallback_email);
      if (result.success) {
        success(result.message || t('platform.notifications.toast.testSent'));
      } else {
        toastError(result.error || t('platform.notifications.toast.testFailed'));
      }
    } finally {
      setTesting(null);
    }
  };

  const handleTestAuth = async (connector: string) => {
    setTestingAuth(connector);
    try {
      const result = await testNotificationConnector(connector, data?.fallback_email);
      if (result.success) {
        success(result.message || t('platform.notifications.toast.authOk'));
        await load();
      } else {
        toastError(result.error || result.message || t('platform.notifications.toast.authFailed'));
      }
    } finally {
      setTestingAuth(null);
    }
  };

  const authBadge = (connector: ConnectorStatus) => {
    if (!connector.configured) {
      return null;
    }
    if (connector.authenticated === false) {
      return (
        <span className="ml-2 text-xs font-medium text-amber-700 dark:text-amber-300">
          {t('platform.notifications.authMissing')}
        </span>
      );
    }
    if (connector.authenticated) {
      return (
        <span className="ml-2 text-xs font-medium text-green-700 dark:text-green-400">
          {t('platform.notifications.authOk')}
        </span>
      );
    }
    return null;
  };

  const handleSendReport = async () => {
    setSendingReport(true);
    try {
      const result = await sendMonitoringReport(true);
      if (result.success) {
        success(t('platform.notifications.toast.reportSent'));
        await load();
      } else {
        const detail =
          result.error ||
          (typeof result.result?.reason === 'string'
            ? t('platform.notifications.toast.reason', { reason: result.result.reason })
            : t('platform.notifications.toast.reportFailed'));
        toastError(detail);
      }
    } finally {
      setSendingReport(false);
    }
  };

  const emailConnectorActive = data?.connectors.some((c) => c.name === 'email' && c.enabled) ?? false;
  const reportConnector = data?.schedule?.connector ?? 'email';
  const needsEmailRecipient = reportConnector === 'email' || reportConnector === 'all';
  const hasRecipient = Boolean(data?.fallback_email);
  const reportBlockers: string[] = [];

  if (data?.schedule) {
    if (reportConnector !== 'all' && !data.connectors.some((c) => c.name === reportConnector && c.enabled)) {
      reportBlockers.push(
        t('platform.notifications.blockers.connectorDisabled', { connector: reportConnector })
      );
    }
    if (reportConnector === 'all' && data.active_adapters.length === 0) {
      reportBlockers.push(t('platform.notifications.blockers.noConnector'));
    }
    if (needsEmailRecipient && !hasRecipient) {
      reportBlockers.push(t('platform.notifications.blockers.missingEmail'));
    }
  }

  const handleRunSchedule = async () => {
    setRunningSchedule(true);
    try {
      const result = await runMonitoringSchedule();
      if (result) {
        success(t('platform.notifications.toast.cronDone'));
        await load();
      } else {
        toastError(t('platform.notifications.toast.scheduleFailed'));
      }
    } finally {
      setRunningSchedule(false);
    }
  };

  const intervalLabel = (interval?: string) => {
    if (interval === 'hour') return t('platform.notifications.intervalHour');
    if (interval === 'week') return t('platform.notifications.intervalWeek');
    return t('platform.notifications.intervalDay');
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (!data) {
    return (
      <div className="card">
        <div className="card-body text-center text-gray-500">{t('platform.notifications.loadFailed')}</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-3">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t('platform.notifications.title')}</h1>
        <div className="flex gap-2">
          <Link to={settingsGroupPath('smtp')} className="btn btn-secondary inline-flex items-center gap-2">
            <Settings className="w-4 h-4" />
            {t('platform.notifications.smtpLink')}
          </Link>
          <button onClick={() => void load()} className="btn btn-secondary">
            {t('platform.notifications.refresh')}
          </button>
        </div>
      </div>

      {data.schedule && (
        <div className="card">
          <div className="card-body">
            <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
              <h2 className="text-lg font-semibold">{t('platform.notifications.scheduledReports')}</h2>
              <div className="flex gap-2">
                <button
                  type="button"
                  className="btn btn-secondary text-sm"
                  disabled={sendingReport}
                  onClick={() => void handleSendReport()}
                >
                  {sendingReport ? t('platform.notifications.sending') : t('platform.notifications.sendReport')}
                </button>
                <button
                  type="button"
                  className="btn btn-secondary text-sm"
                  disabled={runningSchedule}
                  onClick={() => void handleRunSchedule()}
                >
                  {runningSchedule ? t('platform.notifications.running') : t('platform.notifications.simulateCron')}
                </button>
              </div>
            </div>
            <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
              <div>
                <dt className="text-gray-500">{t('platform.notifications.status')}</dt>
                <dd className="font-medium">
                  {data.schedule.enabled ? t('platform.notifications.enabled') : t('platform.notifications.disabled')}
                  {!data.schedule.enabled && (
                    <span className="block text-xs font-normal text-gray-400 mt-0.5">
                      {t('platform.notifications.disabledHint')}
                    </span>
                  )}
                </dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.interval')}</dt>
                <dd className="font-medium capitalize">{intervalLabel(data.schedule.interval)}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.sendTime')}</dt>
                <dd className="font-medium">
                  {data.schedule.interval === 'hour'
                    ? t('platform.notifications.minutePrefix', {
                        minute: String(data.schedule.minute ?? 0).padStart(2, '0'),
                      })
                    : data.schedule.time}
                  {data.schedule.interval === 'week' ? ` · ${data.schedule.weekday}` : ''}
                </dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.connector')}</dt>
                <dd className="font-medium">{data.schedule.connector}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.lastReport')}</dt>
                <dd className="font-medium">{data.schedule.last_sent_at || '—'}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.dueNow')}</dt>
                <dd className="font-medium">
                  {data.schedule.due_now ? t('platform.notifications.yes') : t('platform.notifications.no')}
                </dd>
              </div>
            </dl>
            {data.log_incidents && (
              <p className="text-sm text-gray-500 mt-4">
                {t('platform.notifications.logIncidents', {
                  errors: data.log_incidents.notify_errors ? '✓' : '✗',
                  warnings: data.log_incidents.notify_warnings ? '✓' : '✗',
                  connector: data.log_incidents.connector,
                })}
              </p>
            )}
            {reportBlockers.length > 0 && (
              <div className="mt-4 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-3 py-2 text-sm text-amber-900 dark:text-amber-200">
                <p className="font-medium mb-1">{t('platform.notifications.beforeReport')}</p>
                <ul className="list-disc list-inside space-y-1">
                  {reportBlockers.map((msg) => (
                    <li key={msg}>{msg}</li>
                  ))}
                </ul>
              </div>
            )}
            {reportConnector === 'email' && emailConnectorActive && hasRecipient && (
              <p className="text-xs text-gray-500 mt-3">{t('platform.notifications.email422Hint')}</p>
            )}
            <p className="text-xs text-gray-400 mt-2">{t('platform.notifications.cronHint')}</p>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="card">
          <div className="card-body">
            <h2 className="text-lg font-semibold mb-3">{t('platform.notifications.connectors')}</h2>
            <p className="text-sm text-gray-500 mb-4">
              {t('platform.notifications.alertsEnabled', {
                status: data.alerts_enabled
                  ? t('platform.notifications.alertsOn')
                  : t('platform.notifications.alertsOff'),
              })}{' '}
              · {t('platform.notifications.fallback')}{' '}
              {data.fallback_email || t('platform.notifications.fallbackNotSet')}
            </p>
            <ul className="space-y-2">
              {data.connectors.map((c) => (
                <li key={c.name} className="flex items-center justify-between gap-3 flex-wrap">
                  <span className="min-w-0">
                    <span
                      className={`inline-block w-2 h-2 rounded-full mr-2 ${
                        c.enabled ? 'bg-green-500' : c.configured ? 'bg-amber-400' : 'bg-gray-300'
                      }`}
                    />
                    {c.label}
                    {authBadge(c)}
                    {c.name === 'ntfy' && c.auth_mode && c.auth_mode !== 'none' && (
                      <span className="ml-1 text-xs text-gray-400">({c.auth_mode})</span>
                    )}
                  </span>
                  <span className="flex items-center gap-2 shrink-0">
                    {c.configured && c.authenticated === false && (
                      <button
                        type="button"
                        className="text-sm text-amber-700 dark:text-amber-300 hover:underline disabled:opacity-50"
                        disabled={testingAuth === c.name}
                        onClick={() => void handleTestAuth(c.name)}
                      >
                        {testingAuth === c.name
                          ? t('platform.notifications.checking')
                          : t('platform.notifications.verifyAuth')}
                      </button>
                    )}
                    {c.enabled && (
                      <button
                        type="button"
                        className="text-sm text-indigo-600 hover:underline disabled:opacity-50"
                        disabled={testing === c.name}
                        onClick={() => void handleTest(c.name)}
                      >
                        {testing === c.name ? t('platform.notifications.sendingTest') : t('platform.notifications.test')}
                      </button>
                    )}
                  </span>
                </li>
              ))}
            </ul>
          </div>
        </div>

        <div className="card">
          <div className="card-body">
            <h2 className="text-lg font-semibold mb-3">{t('platform.notifications.visitOverview')}</h2>
            <dl className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt className="text-gray-500">{t('platform.notifications.visits')}</dt>
                <dd className="text-xl font-semibold">{data.analytics.visits}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.pageViews')}</dt>
                <dd className="text-xl font-semibold">{data.analytics.page_views}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.uniqueVisitors')}</dt>
                <dd className="text-xl font-semibold">{data.analytics.unique_visitors}</dd>
              </div>
              <div>
                <dt className="text-gray-500">{t('platform.notifications.realtime')}</dt>
                <dd className="text-xl font-semibold">{data.analytics.realtime_visitors}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-body">
          <h2 className="text-lg font-semibold mb-3">{t('platform.notifications.topPages')}</h2>
          {data.top_pages.length === 0 ? (
            <p className="text-sm text-gray-500">{t('platform.notifications.noPageViews')}</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-gray-500 border-b dark:border-gray-700">
                  <th className="py-2">{t('platform.notifications.path')}</th>
                  <th className="py-2 text-right">{t('platform.notifications.views')}</th>
                </tr>
              </thead>
              <tbody>
                {data.top_pages.map((p) => (
                  <tr key={p.uri} className="border-b dark:border-gray-800">
                    <td className="py-2 font-mono text-xs">{p.uri}</td>
                    <td className="py-2 text-right">{p.views}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
};

export default NotificationsOverview;
