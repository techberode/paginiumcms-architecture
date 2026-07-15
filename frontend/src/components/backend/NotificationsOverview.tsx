// frontend/src/components/backend/NotificationsOverview.tsx
// === Notifications & analytics overview (Iteration 6) ===
import React, { useEffect, useState } from 'react';
import {
  getNotificationOverview,
  sendTestNotification,
  NotificationOverview,
} from '../../api/notifications';
import { useToast } from '../../hooks/useToast';

export const NotificationsOverview: React.FC = () => {
  const [data, setData] = useState<NotificationOverview | null>(null);
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState<string | null>(null);
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
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
        <button onClick={() => void load()} className="btn btn-secondary">
          Refresh
        </button>
      </div>

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
                <li key={c.name} className="flex items-center justify-between gap-3">
                  <span>
                    <span
                      className={`inline-block w-2 h-2 rounded-full mr-2 ${
                        c.enabled ? 'bg-green-500' : 'bg-gray-300'
                      }`}
                    />
                    {c.label}
                  </span>
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
