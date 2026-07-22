// frontend/src/components/dashboard/LogsPanel.tsx
import React from 'react';
import { Link } from 'react-router-dom';
import { ScrollText } from 'lucide-react';
import type { LogSeverity } from '../../api/logs';
import { LOG_SEVERITY_COLORS } from '../../api/logs';
import { logsSeverityPath } from '../../utils/adminDeepLinks';
import { useI18n } from '../../context/I18nContext';

interface LogsPanelProps {
  bySeverity: Partial<Record<LogSeverity, number>>;
  hours?: number;
}

const ORDER: LogSeverity[] = ['critical', 'error', 'warning', 'info', 'debug'];

export const LogsPanel: React.FC<LogsPanelProps> = ({ bySeverity, hours = 24 }) => {
  const { t } = useI18n();
  const total = ORDER.reduce((sum, key) => sum + (bySeverity[key] ?? 0), 0);

  return (
    <div className="card">
      <div className="card-header flex items-center justify-between gap-3">
        <div className="flex items-center gap-2">
          <ScrollText className="w-5 h-5 text-indigo-600" />
          <h3 className="font-bold text-slate-900 dark:text-white">
            {t('dashboard.panels.logs.title', { hours: String(hours) })}
          </h3>
        </div>
        <Link
          to="/logs"
          className="text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
        >
          {t('dashboard.panels.logs.open')}
        </Link>
      </div>
      <div className="card-body space-y-3">
        <div className="text-2xl font-black text-slate-900 dark:text-white">{total}</div>
        <div className="flex flex-wrap gap-2">
          {ORDER.map((severity) => (
            <Link
              key={severity}
              to={logsSeverityPath(severity)}
              className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold ${LOG_SEVERITY_COLORS[severity]}`}
            >
              {t(`logs.severity.${severity}`)}
              <span>{bySeverity[severity] ?? 0}</span>
            </Link>
          ))}
        </div>
      </div>
    </div>
  );
};
