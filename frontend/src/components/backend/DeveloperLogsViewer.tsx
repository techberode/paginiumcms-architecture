// frontend/src/components/backend/DeveloperLogsViewer.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { useApi } from '../../hooks/useApi';
import { useI18n } from '../../context/I18nContext';

interface DevLogEntry {
  ts?: string;
  channel?: string;
  event?: string;
  message?: string;
  context?: Record<string, unknown>;
}

export const DeveloperLogsViewer: React.FC = () => {
  const { t } = useI18n();
  const { get } = useApi();
  const [logs, setLogs] = useState<DevLogEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await get<{ success: boolean; data: DevLogEntry[]; error?: string }>(
        '/api/admin/developer/logs?limit=200'
      );
      if (response.success === false) {
        setError(response.error ?? t('platform.developerLogs.notUnlocked'));
        setLogs([]);
      } else {
        setLogs(Array.isArray(response.data) ? response.data : []);
      }
    } catch {
      setError(t('platform.developerLogs.loadFailed'));
      setLogs([]);
    } finally {
      setLoading(false);
    }
  }, [get, t]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-black text-slate-900 dark:text-white">{t('platform.developerLogs.title')}</h1>
        <button type="button" className="btn btn-secondary" onClick={load} disabled={loading}>
          {t('platform.developerLogs.refresh')}
        </button>
      </div>

      {error && (
        <div className="rounded-xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 px-4 py-3 text-sm text-amber-900 dark:text-amber-100">
          {error}
        </div>
      )}

      {loading ? (
        <div className="flex justify-center py-12">
          <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
        </div>
      ) : (
        <div className="card">
          <div className="card-body max-h-[70vh] overflow-auto font-mono text-xs space-y-2">
            {logs.length === 0 ? (
              <p className="text-slate-500">{t('platform.developerLogs.empty')}</p>
            ) : (
              logs.map((entry, index) => (
                <pre key={index} className="whitespace-pre-wrap break-all border-b border-slate-100 dark:border-slate-800 pb-2">
                  {JSON.stringify(entry, null, 2)}
                </pre>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default DeveloperLogsViewer;
