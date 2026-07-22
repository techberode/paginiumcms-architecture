// frontend/src/components/backend/SecurityAuditManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Download, ShieldAlert } from 'lucide-react';
import { securityApi, type SecurityAuditEvent } from '../../api/security';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

const TYPE_KEYS = [
  'failed_login',
  'successful_login',
  'sso_login',
  'permission_denied',
  'role_denied',
  'settings_change',
] as const;

export const SecurityAuditManager: React.FC = () => {
  const { t } = useI18n();
  const [events, setEvents] = useState<SecurityAuditEvent[]>([]);
  const [loading, setLoading] = useState(true);
  const [typeFilter, setTypeFilter] = useState('');
  const toast = useToast();

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await securityApi.listAudit({
        type: typeFilter || undefined,
        limit: 100,
      });
      setEvents(data.events);
    } catch {
      toast.error(t('platform.securityAudit.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [typeFilter, toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const handleExport = async () => {
    try {
      const blob = await securityApi.exportAuditCsv({ type: typeFilter || undefined });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `security_audit_${new Date().toISOString().slice(0, 10)}.csv`;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      toast.error(t('platform.securityAudit.toast.exportFailed'));
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <ShieldAlert className="w-7 h-7 text-rose-500" />
            {t('platform.securityAudit.title')}
          </h1>
          <p className="text-sm text-slate-500 mt-1">{t('platform.securityAudit.subtitle')}</p>
        </div>
        <button
          type="button"
          onClick={() => void handleExport()}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
        >
          <Download className="w-4 h-4" />
          {t('platform.securityAudit.exportCsv')}
        </button>
      </div>

      <div className="flex gap-3">
        <select
          value={typeFilter}
          onChange={(e) => setTypeFilter(e.target.value)}
          className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
        >
          <option value="">{t('platform.securityAudit.allTypes')}</option>
          {TYPE_KEYS.map((typeKey) => (
            <option key={typeKey} value={typeKey}>
              {t(`platform.securityAudit.types.${typeKey}`)}
            </option>
          ))}
        </select>
      </div>

      {loading ? (
        <div className="py-12 text-center text-slate-500">{t('platform.securityAudit.loading')}</div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 dark:bg-slate-900/80 text-left">
              <tr>
                <th className="px-4 py-3">{t('platform.securityAudit.columns.time')}</th>
                <th className="px-4 py-3">{t('platform.securityAudit.columns.type')}</th>
                <th className="px-4 py-3">{t('platform.securityAudit.columns.message')}</th>
                <th className="px-4 py-3">{t('platform.securityAudit.columns.user')}</th>
                <th className="px-4 py-3">{t('platform.securityAudit.columns.ip')}</th>
              </tr>
            </thead>
            <tbody>
              {events.map((event) => (
                <tr key={event.id} className="border-t border-slate-100 dark:border-slate-800">
                  <td className="px-4 py-3 whitespace-nowrap">{event.created_at}</td>
                  <td className="px-4 py-3 font-mono text-xs">{event.type}</td>
                  <td className="px-4 py-3">{event.message}</td>
                  <td className="px-4 py-3">{event.email ?? event.user_id ?? '—'}</td>
                  <td className="px-4 py-3 font-mono text-xs">{event.ip ?? '—'}</td>
                </tr>
              ))}
              {events.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-slate-500">
                    {t('platform.securityAudit.empty')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default SecurityAuditManager;
