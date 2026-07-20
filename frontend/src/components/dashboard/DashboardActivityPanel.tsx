import React from 'react';
import { Activity, Clock3 } from 'lucide-react';

interface AuditEvent {
  log?: {
    message?: string;
    created_at?: string;
    timestamp?: string | number;
  };
  action?: string;
  created_at?: string;
}

export interface DashboardActivityPanelProps {
  events: Array<Record<string, unknown>>;
  loading?: boolean;
}

function eventMessage(event: Record<string, unknown>): string {
  const audit = event as AuditEvent;
  return audit.log?.message || audit.action || 'Systémová udalosť';
}

function eventTime(event: Record<string, unknown>): string {
  const audit = event as AuditEvent;
  const raw = audit.log?.created_at ?? audit.log?.timestamp ?? audit.created_at;
  if (raw === undefined || raw === null || raw === '') {
    return '';
  }

  const date = typeof raw === 'number' ? new Date(raw * 1000) : new Date(String(raw));
  if (Number.isNaN(date.getTime())) {
    return String(raw);
  }

  return date.toLocaleString('sk-SK');
}

export const DashboardActivityPanel: React.FC<DashboardActivityPanelProps> = ({
  events,
  loading = false,
}) => (
  <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div className="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center gap-2">
      <Activity className="w-5 h-5 text-indigo-500" />
      <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Prehľad aktivít</h2>
    </div>
    <div className="p-6">
      {loading ? (
        <div className="flex justify-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600" />
        </div>
      ) : events.length === 0 ? (
        <p className="text-center text-slate-500 py-8">Zatiaľ žiadne udalosti v audit logu.</p>
      ) : (
        <div className="space-y-3">
          {events.slice(0, 8).map((event, index) => (
            <div
              key={index}
              className="rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/40 px-4 py-3"
            >
              <p className="text-sm text-slate-800 dark:text-slate-100">{eventMessage(event)}</p>
              {eventTime(event) && (
                <p className="mt-1 inline-flex items-center gap-1 text-xs text-slate-500">
                  <Clock3 className="w-3.5 h-3.5" />
                  {eventTime(event)}
                </p>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  </div>
);

export default DashboardActivityPanel;
