import React from 'react';
import { Activity, Clock3, UserRound } from 'lucide-react';
import { Link } from 'react-router-dom';
import {
  formatAuditEventActor,
  formatAuditEventMessage,
  formatAuditEventTimestamp,
} from '../../utils/formatAuditEvent';
import { useI18n } from '../../context/I18nContext';

export interface DashboardActivityPanelProps {
  events: Array<Record<string, unknown>>;
  loading?: boolean;
}

export const DashboardActivityPanel: React.FC<DashboardActivityPanelProps> = ({
  events,
  loading = false,
}) => {
  const { t, locale } = useI18n();

  return (
    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
        <Activity className="w-5 h-5 text-indigo-500" />
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
          {t('dashboard.panels.activity.title')}
        </h2>
      </div>
      <div className="p-6">
        {loading ? (
          <div className="flex justify-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
          </div>
        ) : events.length === 0 ? (
          <p className="text-center text-slate-500 py-8">{t('dashboard.panels.activity.empty')}</p>
        ) : (
          <div className="space-y-3">
            {events.slice(0, 8).map((event, index) => {
              const auditEvent = event as Record<string, unknown>;
              const message = formatAuditEventMessage(auditEvent, locale);
              const actor = formatAuditEventActor(auditEvent, locale);
              const time = formatAuditEventTimestamp(auditEvent, locale);

              return (
                <div
                  key={index}
                  className="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/40 px-4 py-3"
                >
                  <p className="text-sm text-slate-800 dark:text-slate-100">{message}</p>
                  <div className="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    {actor && (
                      <span className="inline-flex items-center gap-1">
                        <UserRound className="w-3.5 h-3.5" />
                        {actor}
                      </span>
                    )}
                    {time && (
                      <span className="inline-flex items-center gap-1">
                        <Clock3 className="w-3.5 h-3.5" />
                        {time}
                      </span>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
        {!loading && events.length > 0 && (
          <div className="px-6 pb-5">
            <Link to="/audit" className="text-sm font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
              {t('dashboard.panels.activity.openAudit')}
            </Link>
          </div>
        )}
      </div>
    </div>
  );
};

export default DashboardActivityPanel;
