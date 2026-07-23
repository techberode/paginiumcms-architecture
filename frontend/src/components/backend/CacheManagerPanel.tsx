// frontend/src/components/backend/CacheManagerPanel.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Database, RefreshCw, Trash2 } from 'lucide-react';
import { getCacheStats, type CacheStats } from '../../api/cache';
import { useCachePurge } from '../../hooks/useCachePurge';
import { useI18n } from '../../context/I18nContext';

export const CacheManagerPanel: React.FC = () => {
  const { t } = useI18n();
  const [stats, setStats] = useState<CacheStats | null>(null);
  const [loading, setLoading] = useState(true);
  const { purge, purging } = useCachePurge();

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
    const ok = await purge(scope);
    if (ok) {
      await load();
    }
  };

  return (
    <div className="card">
      <div className="card-body space-y-4">
        <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
          <div className="flex items-start gap-3">
            <Database className="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
            <div>
              <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                {t('settings.cache.title')}
              </h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {t('settings.cache.description')}
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
            {t('settings.cache.refresh')}
          </button>
        </div>

        {loading && !stats ? (
          <div className="text-sm text-gray-500 dark:text-gray-400">{t('settings.cache.loading')}</div>
        ) : stats ? (
          <dl className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">{t('settings.cache.fileEntries')}</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.file_entries}</dd>
            </div>
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">{t('settings.cache.pagesGeneration')}</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.generations.pages}</dd>
            </div>
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">{t('settings.cache.articlesGeneration')}</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.generations.articles}</dd>
            </div>
            <div className="rounded-lg bg-gray-50 dark:bg-slate-800/60 px-3 py-2">
              <dt className="text-gray-500 dark:text-gray-400">{t('settings.cache.feedsGeneration')}</dt>
              <dd className="font-semibold text-gray-900 dark:text-white">{stats.generations.feeds}</dd>
            </div>
          </dl>
        ) : (
          <div className="text-sm text-amber-700 dark:text-amber-300">{t('settings.cache.loadFailed')}</div>
        )}

        <div className="flex flex-wrap gap-2 pt-1">
          <button
            type="button"
            onClick={() => void handlePurge('content')}
            disabled={loading || purging !== null}
            className="btn btn-primary inline-flex items-center gap-2"
          >
            <Trash2 className="w-4 h-4" />
            {purging === 'content' ? t('settings.cache.purging') : t('settings.cache.purgeContent')}
          </button>
          <button
            type="button"
            onClick={() => void handlePurge('all')}
            disabled={loading || purging !== null}
            className="btn btn-secondary inline-flex items-center gap-2"
          >
            <Trash2 className="w-4 h-4" />
            {purging === 'all' ? t('settings.cache.purging') : t('settings.cache.purgeAll')}
          </button>
        </div>
      </div>
    </div>
  );
};

export default CacheManagerPanel;
