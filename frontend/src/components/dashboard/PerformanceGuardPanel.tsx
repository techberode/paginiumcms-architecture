// frontend/src/components/dashboard/PerformanceGuardPanel.tsx
import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { clearApmSamples, type ApmOverview } from '../../api/metrics';

interface Props {
  overview: ApmOverview | null;
  loading?: boolean;
  onRefresh?: () => void;
}

export const PerformanceGuardPanel: React.FC<Props> = ({ overview, loading, onRefresh }) => {
  const { t } = useI18n();
  const toast = useToast();
  const [clearing, setClearing] = useState(false);

  const handleClearSamples = async () => {
    if (!window.confirm(t('dashboard.panels.apm.clearConfirm'))) {
      return;
    }

    setClearing(true);
    try {
      const ok = await clearApmSamples();
      if (ok) {
        toast.success(t('dashboard.panels.apm.clearSuccess'));
        onRefresh?.();
      } else {
        toast.error(t('dashboard.panels.apm.clearFailed'));
      }
    } finally {
      setClearing(false);
    }
  };

  const canClear =
    overview?.config.enabled === true &&
    (overview.summary.sample_count > 0 || overview.recent_breaches.length > 0);

  return (
    <div className="card">
      <div className="card-body">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('dashboard.panels.apm.title')}
          </h2>
          <Link to="/settings" className="text-sm text-indigo-600 hover:underline">
            {t('dashboard.panels.apm.settingsLink')}
          </Link>
        </div>

        {loading || !overview ? (
          <div className="flex justify-center py-6">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600" />
          </div>
        ) : !overview.config.enabled ? (
          <p className="text-sm text-gray-600 dark:text-gray-300">{t('dashboard.panels.apm.disabled')}</p>
        ) : (
          <>
            <dl className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center mb-4">
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.apm.p95')}</dt>
                <dd className="text-lg font-semibold">{overview.summary.p95_ms ?? '—'} ms</dd>
              </div>
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.apm.errorRate')}</dt>
                <dd className="text-lg font-semibold">{(overview.summary.error_rate * 100).toFixed(1)}%</dd>
              </div>
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.apm.samples')}</dt>
                <dd className="text-lg font-semibold">{overview.summary.sample_count}</dd>
              </div>
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.apm.breaches')}</dt>
                <dd className="text-lg font-semibold">{overview.recent_breaches.length}</dd>
              </div>
            </dl>
            {(overview.summary.storage_ms_p95 != null || overview.summary.session_lock_ms_p95 != null) && (
              <dl className="grid grid-cols-2 gap-3 text-center mb-4">
                {overview.summary.storage_ms_p95 != null && (
                  <div>
                    <dt className="text-xs text-gray-500">{t('dashboard.panels.apm.storageP95')}</dt>
                    <dd className="text-lg font-semibold">{overview.summary.storage_ms_p95} ms</dd>
                  </div>
                )}
                {overview.summary.session_lock_ms_p95 != null && (
                  <div>
                    <dt className="text-xs text-gray-500">{t('dashboard.panels.apm.sessionLockP95')}</dt>
                    <dd className="text-lg font-semibold">{overview.summary.session_lock_ms_p95} ms</dd>
                  </div>
                )}
              </dl>
            )}
            <div className="flex flex-wrap items-center justify-between gap-2">
              <p className="text-xs text-gray-500 dark:text-gray-400">{overview.host_metrics_note}</p>
              {canClear ? (
                <button
                  type="button"
                  className="text-xs text-red-600 hover:underline shrink-0 disabled:opacity-50"
                  disabled={clearing}
                  onClick={() => void handleClearSamples()}
                >
                  {clearing ? t('dashboard.panels.apm.clearing') : t('dashboard.panels.apm.clearSamples')}
                </button>
              ) : null}
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default PerformanceGuardPanel;
