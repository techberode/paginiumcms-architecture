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

export const NotificationsOverview: React.FC = () => {
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
        success(result.message || 'Test notification sent');
      } else {
        toastError(result.error || 'Test failed');
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
        success(result.message || 'Connector auth OK');
        await load();
      } else {
        toastError(result.error || result.message || 'Connector auth test failed');
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
          Chýba auth
        </span>
      );
    }
    if (connector.authenticated) {
      return (
        <span className="ml-2 text-xs font-medium text-green-700 dark:text-green-400">
          Auth OK
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
        success('Monitoring report odoslaný');
        await load();
      } else {
        const detail =
          result.error ||
          (typeof result.result?.reason === 'string'
            ? `Dôvod: ${result.result.reason}`
            : 'Report sa nepodarilo odoslať');
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
        `Konektor „${reportConnector}“ nie je zapnutý – Settings → Connectors (SMTP & konektory).`
      );
    }
    if (reportConnector === 'all' && data.active_adapters.length === 0) {
      reportBlockers.push('Nie je zapnutý žiadny konektor.');
    }
    if (needsEmailRecipient && !hasRecipient) {
      reportBlockers.push('Chýba alert email (Monitoring) alebo admin email (General).');
    }
  }

  const handleRunSchedule = async () => {
    setRunningSchedule(true);
    try {
      const result = await runMonitoringSchedule();
      if (result) {
        success('Cron simulácia dokončená');
        await load();
      } else {
        toastError('Spustenie plánovača zlyhalo');
      }
    } finally {
      setRunningSchedule(false);
    }
  };

  const intervalLabel = (interval?: string) => {
    if (interval === 'hour') return 'hodina';
    if (interval === 'week') return 'týždeň';
    return 'deň';
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
        <div className="card-body text-center text-gray-500">Failed to load notification overview.</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center flex-wrap gap-3">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
        <div className="flex gap-2">
          <Link to="/settings" className="btn btn-secondary inline-flex items-center gap-2">
            <Settings className="w-4 h-4" />
            SMTP &amp; konektory
          </Link>
          <button onClick={() => void load()} className="btn btn-secondary">
            Refresh
          </button>
        </div>
      </div>

      {data.schedule && (
        <div className="card">
          <div className="card-body">
            <div className="flex flex-wrap items-start justify-between gap-3 mb-3">
              <h2 className="text-lg font-semibold">Plánované reporty (It.7)</h2>
              <div className="flex gap-2">
                <button
                  type="button"
                  className="btn btn-secondary text-sm"
                  disabled={sendingReport}
                  onClick={() => void handleSendReport()}
                >
                  {sendingReport ? 'Odosielam…' : 'Odoslať report teraz'}
                </button>
                <button
                  type="button"
                  className="btn btn-secondary text-sm"
                  disabled={runningSchedule}
                  onClick={() => void handleRunSchedule()}
                >
                  {runningSchedule ? 'Beží…' : 'Simulovať cron'}
                </button>
              </div>
            </div>
            <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
              <div>
                <dt className="text-gray-500">Stav</dt>
                <dd className="font-medium">
                  {data.schedule.enabled ? 'Zapnuté' : 'Vypnuté'}
                  {!data.schedule.enabled && (
                    <span className="block text-xs font-normal text-gray-400 mt-0.5">
                      Cron neposiela; „Odoslať teraz“ funguje aj pri Vypnuté.
                    </span>
                  )}
                </dd>
              </div>
              <div>
                <dt className="text-gray-500">Interval</dt>
                <dd className="font-medium capitalize">{intervalLabel(data.schedule.interval)}</dd>
              </div>
              <div>
                <dt className="text-gray-500">Čas odoslania</dt>
                <dd className="font-medium">
                  {data.schedule.interval === 'hour'
                    ? `minúta :${String(data.schedule.minute ?? 0).padStart(2, '0')}`
                    : data.schedule.time}
                  {data.schedule.interval === 'week' ? ` · ${data.schedule.weekday}` : ''}
                </dd>
              </div>
              <div>
                <dt className="text-gray-500">Konektor</dt>
                <dd className="font-medium">{data.schedule.connector}</dd>
              </div>
              <div>
                <dt className="text-gray-500">Posledný report</dt>
                <dd className="font-medium">{data.schedule.last_sent_at || '—'}</dd>
              </div>
              <div>
                <dt className="text-gray-500">Due now</dt>
                <dd className="font-medium">{data.schedule.due_now ? 'Áno' : 'Nie'}</dd>
              </div>
            </dl>
            {data.log_incidents && (
              <p className="text-sm text-gray-500 mt-4">
                Log incidenty: ERROR {data.log_incidents.notify_errors ? '✓' : '✗'} · WARNING{' '}
                {data.log_incidents.notify_warnings ? '✓' : '✗'} · konektor{' '}
                {data.log_incidents.connector}
              </p>
            )}
            {reportBlockers.length > 0 && (
              <div className="mt-4 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-3 py-2 text-sm text-amber-900 dark:text-amber-200">
                <p className="font-medium mb-1">Pred odoslaním reportu treba:</p>
                <ul className="list-disc list-inside space-y-1">
                  {reportBlockers.map((msg) => (
                    <li key={msg}>{msg}</li>
                  ))}
                </ul>
              </div>
            )}
            {reportConnector === 'email' && emailConnectorActive && hasRecipient && (
              <p className="text-xs text-gray-500 mt-3">
                Ak stále 422: v sekcii Connectors otestuj Email – rovnaké SMTP musí prejsť aj pre report.
              </p>
            )}
            <p className="text-xs text-gray-400 mt-2">
              Cron: <code className="font-mono">php backend/bin/console monitoring:run-schedule</code>{' '}
              (každú minútu). Nastavenia v Settings → Monitoring.
            </p>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="card">
          <div className="card-body">
            <h2 className="text-lg font-semibold mb-3">Connectors</h2>
            <p className="text-sm text-gray-500 mb-4">
              Alerts: {data.alerts_enabled ? 'enabled' : 'disabled'} · Fallback:{' '}
              {data.fallback_email || 'not set'}
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
                        {testingAuth === c.name ? 'Checking…' : 'Verify auth'}
                      </button>
                    )}
                    {c.enabled && (
                      <button
                        type="button"
                        className="text-sm text-indigo-600 hover:underline disabled:opacity-50"
                        disabled={testing === c.name}
                        onClick={() => void handleTest(c.name)}
                      >
                        {testing === c.name ? 'Sending…' : 'Test'}
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
            <h2 className="text-lg font-semibold mb-3">Visit overview (today)</h2>
            <dl className="grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt className="text-gray-500">Visits</dt>
                <dd className="text-xl font-semibold">{data.analytics.visits}</dd>
              </div>
              <div>
                <dt className="text-gray-500">Page views</dt>
                <dd className="text-xl font-semibold">{data.analytics.page_views}</dd>
              </div>
              <div>
                <dt className="text-gray-500">Unique visitors</dt>
                <dd className="text-xl font-semibold">{data.analytics.unique_visitors}</dd>
              </div>
              <div>
                <dt className="text-gray-500">Realtime</dt>
                <dd className="text-xl font-semibold">{data.analytics.realtime_visitors}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <div className="card">
        <div className="card-body">
          <h2 className="text-lg font-semibold mb-3">Top pages (today)</h2>
          {data.top_pages.length === 0 ? (
            <p className="text-sm text-gray-500">No page views recorded yet.</p>
          ) : (
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-gray-500 border-b dark:border-gray-700">
                  <th className="py-2">Path</th>
                  <th className="py-2 text-right">Views</th>
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
