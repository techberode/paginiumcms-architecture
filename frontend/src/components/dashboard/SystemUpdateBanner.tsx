import React, { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowUpCircle, RefreshCw, X } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useSettings } from '../../hooks/useSettings';
import { useI18n } from '../../context/I18nContext';
import { checkSystemUpdate, type SystemUpdateCheckResult } from '../../api/systemUpdate';

export const SystemUpdateBanner: React.FC = () => {
  const { t } = useI18n();
  const { user } = useAuth();
  const { settings } = useSettings();
  const [check, setCheck] = useState<SystemUpdateCheckResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [dismissed, setDismissed] = useState(false);

  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const isDemoInstance = settings.demo?.enabled === true;

  const loadCheck = useCallback(async () => {
    if (!isSuperAdmin || isDemoInstance) {
      return;
    }

    setLoading(true);
    try {
      const result = await checkSystemUpdate();
      setCheck(result);
    } finally {
      setLoading(false);
    }
  }, [isDemoInstance, isSuperAdmin]);

  useEffect(() => {
    void loadCheck();
  }, [loadCheck]);

  if (!isSuperAdmin || isDemoInstance || dismissed) {
    return null;
  }

  const latestTag =
    check?.update?.latest_tag ??
    check?.remote.latest_release_tag ??
    null;

  if (check?.update?.status !== 'update_available' || !latestTag) {
    return null;
  }

  return (
    <div className="rounded-2xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50 dark:bg-indigo-950/40 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center gap-4">
      <div className="flex items-start gap-3 flex-1 min-w-0">
        <div className="p-2 rounded-xl bg-indigo-600 text-white shrink-0">
          <ArrowUpCircle className="w-5 h-5" />
        </div>
        <div className="min-w-0">
          <p className="font-bold text-indigo-950 dark:text-indigo-100">
            {t('dashboard.updateBanner.title')}
          </p>
          <p className="text-sm text-indigo-900/80 dark:text-indigo-200/80 mt-1">
            {t('dashboard.updateBanner.message', { version: latestTag })}
          </p>
        </div>
      </div>

      <div className="flex items-center gap-2 shrink-0">
        <button
          type="button"
          onClick={() => void loadCheck()}
          disabled={loading}
          className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 text-sm font-semibold hover:bg-indigo-100/70 dark:hover:bg-indigo-900/40 disabled:opacity-60"
        >
          <RefreshCw className={`w-4 h-4 ${loading ? 'animate-spin' : ''}`} />
          {t('dashboard.updateBanner.refresh')}
        </button>
        <Link
          to="/platform/update"
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold shadow-sm"
        >
          {t('dashboard.updateBanner.action')}
        </Link>
        <button
          type="button"
          onClick={() => setDismissed(true)}
          className="p-2 rounded-xl text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100/70 dark:hover:bg-indigo-900/40"
          aria-label={t('dashboard.updateBanner.dismiss')}
        >
          <X className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
};
