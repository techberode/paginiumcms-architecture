// frontend/src/components/dashboard/PerformanceGuardPanel.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { useI18n } from '../../context/I18nContext';
import type { ApmOverview } from '../../api/metrics';

interface Props {
  overview: ApmOverview | null;
  loading?: boolean;
}

export const PerformanceGuardPanel: React.FC<Props> = ({ overview, loading }) => {
  const { t } = useI18n();

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
            <p className="text-xs text-gray-500 dark:text-gray-400">{overview.host_metrics_note}</p>
          </>
        )}
      </div>
    </div>
  );
};

export default PerformanceGuardPanel;
