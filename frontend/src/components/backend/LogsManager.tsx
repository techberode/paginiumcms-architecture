// frontend/src/components/backend/LogsManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ExternalLink, Loader2, ScrollText, Trash2 } from 'lucide-react';
import {
  logsApi,
  LOG_SEVERITY_COLORS,
  LOG_SEVERITY_LABELS,
  type LogEntry,
  type LogSeverity,
  type LogStats,
} from '../../api/logs';
import { useToast } from '../../hooks/useToast';
import { AdminListToolbar } from './AdminListToolbar';

const SEVERITIES: LogSeverity[] = ['debug', 'info', 'warning', 'error', 'critical'];

export const LogsManager: React.FC = () => {
  const toast = useToast();
  const [searchParams, setSearchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState<LogStats | null>(null);
  const [items, setItems] = useState<LogEntry[]>([]);
  const [search, setSearch] = useState('');
  const [severity, setSeverity] = useState<LogSeverity | ''>(
    (searchParams.get('severity') as LogSeverity) || ''
  );
  const [source, setSource] = useState('');
  const [purging, setPurging] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [statsData, listData] = await Promise.all([
        logsApi.stats(24),
        logsApi.list({
          limit: 200,
          severity: severity || undefined,
          source: source || undefined,
          search: search || undefined,
        }),
      ]);
      setStats(statsData);
      setItems(listData?.items ?? []);
    } catch {
      toast.error('Nepodarilo sa načítať logy');
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [search, severity, source]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    if (severity) {
      setSearchParams({ severity }, { replace: true });
    } else {
      setSearchParams({}, { replace: true });
    }
  }, [severity, setSearchParams]);

  const handlePurge = async () => {
    if (!confirm('Vymazať log súbory staršie ako retentionDays z Nastavení?')) {
      return;
    }
    setPurging(true);
    try {
      const removed = await logsApi.purge();
      if (removed !== null) {
        toast.success(`Odstránených ${removed} starých log súborov`);
        await load();
      } else {
        toast.error('Purge zlyhal');
      }
    } finally {
      setPurging(false);
    }
  };

  const sources = useMemo(() => stats?.sources ?? ['app', 'audit', 'event', 'user'], [stats]);

  return (
    <div className="flex-1 overflow-y-auto bg-slate-50 p-4 md:p-8">
      <div className="max-w-7xl mx-auto space-y-6">
        <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div>
            <h1 className="text-2xl font-black text-slate-900 flex items-center gap-2">
              <ScrollText className="w-7 h-7 text-indigo-600" />
              Logy
            </h1>
            <p className="text-sm text-slate-500 mt-1">
              Structured logy (app, audit, event, user) — timestamp a IP na každom zázname.
            </p>
          </div>
          <div className="flex flex-wrap gap-2">
            <Link
              to="/settings"
              state={{ group: 'logging' }}
              className="inline-flex items-center gap-2 text-sm font-bold text-indigo-600 hover:text-indigo-800"
            >
              Nastavenia logov
              <ExternalLink className="w-4 h-4" />
            </Link>
            <button
              type="button"
              disabled={purging}
              onClick={() => void handlePurge()}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-300 disabled:opacity-50 cursor-pointer"
            >
              <Trash2 className="w-4 h-4" />
              Purge starých
            </button>
          </div>
        </div>

        {stats && (
          <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
            {SEVERITIES.map((level) => (
              <button
                key={level}
                type="button"
                onClick={() => setSeverity(severity === level ? '' : level)}
                className={`rounded-2xl border p-4 text-left transition-all cursor-pointer ${
                  severity === level
                    ? 'border-indigo-500 ring-2 ring-indigo-200'
                    : 'border-slate-200 bg-white hover:border-indigo-300'
                }`}
              >
                <div className={`inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold ${LOG_SEVERITY_COLORS[level]}`}>
                  {LOG_SEVERITY_LABELS[level]}
                </div>
                <div className="text-2xl font-black text-slate-900 mt-2">
                  {stats.by_severity[level] ?? 0}
                </div>
                <div className="text-[10px] text-slate-400 uppercase">24 h</div>
              </button>
            ))}
          </div>
        )}

        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-4 border-b border-slate-100 space-y-3">
            <AdminListToolbar search={search} onSearchChange={setSearch} searchPlaceholder="Hľadať v logoch…" />
            <div className="flex flex-wrap gap-2 items-center">
              <span className="text-xs text-slate-500 font-bold">Zdroj:</span>
              <button
                type="button"
                onClick={() => setSource('')}
                className={`px-3 py-1 rounded-lg text-xs font-bold cursor-pointer ${
                  source === '' ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                }`}
              >
                Všetky
              </button>
              {sources.map((src) => (
                <button
                  key={src}
                  type="button"
                  onClick={() => setSource(src)}
                  className={`px-3 py-1 rounded-lg text-xs font-bold cursor-pointer ${
                    source === src ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                  }`}
                >
                  {src}
                </button>
              ))}
            </div>
          </div>

          {loading ? (
            <div className="p-12 flex justify-center text-slate-400">
              <Loader2 className="w-8 h-8 animate-spin" />
            </div>
          ) : (
            <div className="overflow-x-auto max-h-[70vh]">
              <table className="w-full text-sm">
                <thead className="sticky top-0 bg-slate-50 z-10">
                  <tr className="border-b border-slate-100">
                    <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Čas</th>
                    <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Úroveň</th>
                    <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Zdroj</th>
                    <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Kategória</th>
                    <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">IP</th>
                    <th className="px-4 py-3 text-left text-xs font-bold text-slate-500">Správa</th>
                  </tr>
                </thead>
                <tbody>
                  {items.map((entry) => (
                    <tr key={entry.id} className="border-b border-slate-50 hover:bg-slate-50/80 align-top">
                      <td className="px-4 py-3 whitespace-nowrap text-xs text-slate-600">
                        {entry.timestamp}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            LOG_SEVERITY_COLORS[entry.severity] ?? LOG_SEVERITY_COLORS.info
                          }`}
                        >
                          {entry.severity}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-xs font-mono">{entry.source ?? 'app'}</td>
                      <td className="px-4 py-3 text-xs">{entry.category}</td>
                      <td className="px-4 py-3 text-xs font-mono">{entry.ip ?? '—'}</td>
                      <td className="px-4 py-3">
                        <div className="font-medium text-slate-800">{entry.message}</div>
                        {entry.context && Object.keys(entry.context).length > 0 && (
                          <pre className="mt-1 text-[10px] text-slate-500 whitespace-pre-wrap break-all max-w-xl">
                            {JSON.stringify(entry.context, null, 2)}
                          </pre>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
              {items.length === 0 && (
                <div className="p-12 text-center text-slate-400 text-sm">Žiadne záznamy</div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default LogsManager;
