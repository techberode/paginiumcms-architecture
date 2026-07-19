// frontend/src/components/backend/CacheManagerPanel.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Database, RefreshCw, Trash2 } from 'lucide-react';
import { getCacheStats, purgeCache, type CacheStats } from '../../api/cache';
import { useToast } from '../../hooks/useToast';

export const CacheManagerPanel: React.FC = () => {
  const [stats, setStats] = useState<CacheStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [purging, setPurging] = useState<'content' | 'all' | null>(null);
  const toast = useToast();

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await getCacheStats();
      setStats(data);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  const handlePurge = async (scope: 'content' | 'all') => {
    const label = scope === 'all' ? 'celú cache' : 'cache obsahu (stránky, články, feedy)';
    if (!window.confirm(`Naozaj chcete vymazať ${label}?`)) {
      return;
    }

    setPurging(scope);
    try {
      const res = await purgeCache(scope);
      if (res.success && res.data) {
        toast.success(res.message || 'Cache vymazaná');
        await load();
      } else {
        toast.error(res.error || 'Vymazanie cache zlyhalo');
      }
    } finally {
      setPurging(null);
    }
  };

  return (
    <div className="card">
      <div className="card-body space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div className="flex items-start gap-3">
            <Database className="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
            <div>
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white">Cache systému</h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manuálne vymazanie cache po deployi alebo keď verejný web zobrazuje starý obsah.
                Odporúčané: najprv „Cache obsahu“.
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => void load()}
            disabled={loading || purging !== null}
            className="btn btn-secondary shrink-0 inline-flex items-center gap-2"
          >
            <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
            Obnoviť stav
          </button>
        </div>

        {loading && !stats ? (
          <div className="text-sm text-gray-500 dark:text-gray-400">Načítavam stav cache…</div>
        ) : stats ? (
          <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">Súbory na disku</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.file_entries}</dd>
            </div>
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">Generácia stránok</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.generations.pages}</dd>
            </div>
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">Generácia článkov</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.generations.articles}</dd>
            </div>
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">Generácia feedov</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.generations.feeds}</dd>
            </div>
          </dl>
        ) : (
          <div className="text-sm text-amber-700 dark:text-amber-300">Nepodarilo sa načítať stav cache.</div>
        )}

        <div className="flex flex-wrap gap-2 pt-1">
          <button
            type="button"
            onClick={() => void handlePurge('content')}
            disabled={loading || purging !== null}
            className="btn btn-primary inline-flex items-center gap-2"
          >
            <Trash2 className="w-4 h-4" />
            {purging === 'content' ? 'Mažem…' : 'Vymazať cache obsahu'}
          </button>
          <button
            type="button"
            onClick={() => void handlePurge('all')}
            disabled={loading || purging !== null}
            className="btn btn-secondary inline-flex items-center gap-2"
          >
            <Trash2 className="w-4 h-4" />
            {purging === 'all' ? 'Mažem…' : 'Vymazať celú cache'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default CacheManagerPanel;
