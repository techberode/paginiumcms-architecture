// frontend/src/components/dashboard/HealthPanel.tsx
import React from 'react';
import { HealthReport } from '../../api/types';
import { useI18n } from '../../context/I18nContext';

interface HealthPanelProps {
  health: HealthReport | null;
  loading?: boolean;
}

const statusColor: Record<string, string> = {
  pass: 'text-green-600 dark:text-green-400',
  warn: 'text-yellow-600 dark:text-yellow-400',
  fail: 'text-red-600 dark:text-red-400',
};

export const HealthPanel: React.FC<HealthPanelProps> = ({ health, loading }) => {
  const { t } = useI18n();

  return (
    <div className="card">
      <div className="card-body">
        <div className="flex items-center justify-between mb-3">
          <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
            {t('dashboard.panels.health.title')}
          </h2>
          {health && (
            <span className={`text-sm font-medium uppercase ${statusColor[health.status] ?? 'text-gray-500'}`}>
              {health.status}
            </span>
          )}
        </div>

        {loading || !health ? (
          <div className="flex justify-center py-6">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-indigo-600" />
          </div>
        ) : (
          <>
            <dl className="grid grid-cols-4 gap-2 text-center mb-4">
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.health.pass')}</dt>
                <dd className="text-lg font-semibold text-green-600">{health.summary.pass}</dd>
              </div>
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.health.warn')}</dt>
                <dd className="text-lg font-semibold text-yellow-600">{health.summary.warn}</dd>
              </div>
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.health.fail')}</dt>
                <dd className="text-lg font-semibold text-red-600">{health.summary.fail}</dd>
              </div>
              <div>
                <dt className="text-xs text-gray-500">{t('dashboard.panels.health.total')}</dt>
                <dd className="text-lg font-semibold">{health.summary.total}</dd>
              </div>
            </dl>
            <ul className="space-y-2 max-h-48 overflow-y-auto">
              {health.checks.map((check) => (
                <li key={check.name} className="flex items-start justify-between gap-2 text-sm">
                  <span className="text-gray-700 dark:text-gray-200">{check.name}</span>
                  <span className={`uppercase text-xs font-medium ${statusColor[check.status] ?? ''}`}>
                    {check.status}
                  </span>
                </li>
              ))}
            </ul>
          </>
        )}
      </div>
    </div>
  );
};

export default HealthPanel;
