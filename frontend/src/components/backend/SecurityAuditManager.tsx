// frontend/src/components/backend/SecurityAuditManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Download, ShieldAlert } from 'lucide-react';
import { securityApi, type SecurityAuditEvent } from '../../api/security';
import { useToast } from '../../hooks/useToast';

export const SecurityAuditManager: React.FC = () => {
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
      toast.error('Nepodarilo sa načítať bezpečnostný audit');
    } finally {
      setLoading(false);
    }
  }, [typeFilter, toast]);

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
      toast.error('Export zlyhal');
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <ShieldAlert className="w-7 h-7 text-rose-500" />
            Bezpečnostný audit
          </h1>
          <p className="text-sm text-slate-500 mt-1">Prihlásenia, zamietnuté oprávnenia, zmeny nastavení, SSO.</p>
        </div>
        <button
          type="button"
          onClick={() => void handleExport()}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
        >
          <Download className="w-4 h-4" />
          Export CSV
        </button>
      </div>

      <div className="flex gap-3">
        <select
          value={typeFilter}
          onChange={(e) => setTypeFilter(e.target.value)}
          className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
        >
          <option value="">Všetky typy</option>
          <option value="failed_login">Neúspešné prihlásenie</option>
          <option value="successful_login">Úspešné prihlásenie</option>
          <option value="sso_login">SSO prihlásenie</option>
          <option value="permission_denied">Zamietnuté oprávnenie</option>
          <option value="role_denied">Zamietnutá rola</option>
          <option value="settings_change">Zmena nastavení</option>
        </select>
      </div>

      {loading ? (
        <div className="py-12 text-center text-slate-500">Načítavam…</div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 dark:bg-slate-900/80 text-left">
              <tr>
                <th className="px-4 py-3">Čas</th>
                <th className="px-4 py-3">Typ</th>
                <th className="px-4 py-3">Správa</th>
                <th className="px-4 py-3">Používateľ</th>
                <th className="px-4 py-3">IP</th>
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
                    Žiadne záznamy
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
