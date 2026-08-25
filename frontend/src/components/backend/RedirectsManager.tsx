import React, { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ArrowRightLeft, Plus, RefreshCw, Trash2 } from 'lucide-react';
import { redirectsApi, type RedirectRule } from '../../api/redirects';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useI18n } from '../../context/I18nContext';
import { BulkActionBar } from './BulkActionBar';
import { summarizeBulkResult } from '../../types/bulk';

export const RedirectsManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [searchParams] = useSearchParams();
  const [loading, setLoading] = useState(true);
  const [rules, setRules] = useState<RedirectRule[]>([]);
  const [showCreate, setShowCreate] = useState(false);
  const [from, setFrom] = useState('');
  const [to, setTo] = useState('');
  const [status, setStatus] = useState<301 | 302>(301);
  const [note, setNote] = useState('');
  const [creating, setCreating] = useState(false);
  const [busyId, setBusyId] = useState<string | null>(null);

  const bulkSelection = useBulkSelection(
    rules.map((rule) => rule.id),
    String(rules.length)
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const data = await redirectsApi.list();
      setRules(data?.rules ?? []);
    } catch {
      toast.error(t('platform.redirects.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  useEffect(() => {
    const prefilledFrom = searchParams.get('from')?.trim();
    if (prefilledFrom) {
      setFrom(prefilledFrom);
      setShowCreate(true);
    }
  }, [searchParams]);

  const handleCreate = async () => {
    if (!from.trim() || !to.trim()) {
      toast.error(t('platform.redirects.toast.pathsRequired'));
      return;
    }

    setCreating(true);
    try {
      const created = await redirectsApi.create({
        from: from.trim(),
        to: to.trim(),
        status,
        note: note.trim(),
      });
      if (!created.success || !created.data) {
        toast.error(created.error || created.message || t('platform.redirects.toast.createFailed'));
        return;
      }
      toast.success(t('platform.redirects.toast.created'));
      setShowCreate(false);
      setFrom('');
      setTo('');
      setNote('');
      setStatus(301);
      await load();
    } finally {
      setCreating(false);
    }
  };

  const toggleEnabled = async (rule: RedirectRule) => {
    setBusyId(rule.id);
    try {
      const updated = await redirectsApi.update(rule.id, { enabled: !rule.enabled });
      if (!updated.success) {
        toast.error(updated.error || t('platform.redirects.toast.updateFailed'));
        return;
      }
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleBulkDelete = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!window.confirm(t('platform.redirects.confirm.bulkDelete', { count: String(bulkSelection.count) }))) {
      return;
    }
    setBusyId('bulk');
    try {
      const result = await redirectsApi.bulkDelete(bulkSelection.selectedIds);
      if (!result) {
        toast.error(t('platform.redirects.toast.bulkFailed'));
        return;
      }
      toast.success(summarizeBulkResult(result, t));
      bulkSelection.clear();
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const handleDelete = async (rule: RedirectRule) => {
    if (!window.confirm(t('platform.redirects.confirmDelete', { from: rule.from }))) {
      return;
    }
    setBusyId(rule.id);
    try {
      const ok = await redirectsApi.remove(rule.id);
      if (!ok) {
        toast.error(t('platform.redirects.toast.deleteFailed'));
        return;
      }
      toast.success(t('platform.redirects.toast.deleted'));
      await load();
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <ArrowRightLeft className="w-7 h-7 text-indigo-600" />
            {t('platform.redirects.title')}
          </h1>
          <p className="text-sm text-slate-600 dark:text-slate-300 mt-1">{t('platform.redirects.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.redirects.refresh')}
          </button>
          <button
            type="button"
            onClick={() => setShowCreate(true)}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
          >
            <Plus className="w-4 h-4" />
            {t('platform.redirects.create')}
          </button>
        </div>
      </div>

      {showCreate && (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-900">
          <h2 className="font-bold text-slate-900 dark:text-white">{t('platform.redirects.createTitle')}</h2>
          <div className="grid gap-3 md:grid-cols-2">
            <label className="text-sm space-y-1">
              <span>{t('platform.redirects.from')}</span>
              <input
                value={from}
                onChange={(e) => setFrom(e.target.value)}
                placeholder="/blog/old-slug"
                className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
              />
            </label>
            <label className="text-sm space-y-1">
              <span>{t('platform.redirects.to')}</span>
              <input
                value={to}
                onChange={(e) => setTo(e.target.value)}
                placeholder="/articles/new-slug"
                className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
              />
            </label>
          </div>
          <div className="grid gap-3 md:grid-cols-2">
            <label className="text-sm space-y-1">
              <span>{t('platform.redirects.status')}</span>
              <select
                value={status}
                onChange={(e) => setStatus(Number(e.target.value) as 301 | 302)}
                className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
              >
                <option value={301}>301 — {t('platform.redirects.permanent')}</option>
                <option value={302}>302 — {t('platform.redirects.temporary')}</option>
              </select>
            </label>
            <label className="text-sm space-y-1">
              <span>{t('platform.redirects.note')}</span>
              <input
                value={note}
                onChange={(e) => setNote(e.target.value)}
                className="w-full rounded-xl border border-slate-300 dark:border-slate-600 px-3 py-2 bg-white dark:bg-slate-950"
              />
            </label>
          </div>
          <div className="flex gap-2">
            <button
              type="button"
              disabled={creating}
              onClick={() => void handleCreate()}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-50"
            >
              {t('platform.redirects.save')}
            </button>
            <button
              type="button"
              onClick={() => setShowCreate(false)}
              className="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm"
            >
              {t('platform.redirects.cancel')}
            </button>
          </div>
        </div>
      )}

      <BulkActionBar
        count={bulkSelection.count}
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'delete',
            label: t('platform.redirects.delete'),
            variant: 'danger',
            disabled: busyId === 'bulk',
            onClick: () => void handleBulkDelete(),
          },
        ]}
      />

      <div className="rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <table className="min-w-full text-sm">
          <thead className="bg-slate-50 dark:bg-slate-800/60 text-left">
            <tr>
              <th className="px-4 py-3 w-10">
                {!loading && rules.length > 0 ? (
                  <input
                    type="checkbox"
                    checked={bulkSelection.allSelected}
                    onChange={() => bulkSelection.toggleAll()}
                    aria-label={t('platform.redirects.delete')}
                  />
                ) : null}
              </th>
              <th className="px-4 py-3">{t('platform.redirects.from')}</th>
              <th className="px-4 py-3">{t('platform.redirects.to')}</th>
              <th className="px-4 py-3">{t('platform.redirects.status')}</th>
              <th className="px-4 py-3">{t('platform.redirects.enabled')}</th>
              <th className="px-4 py-3">{t('platform.redirects.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {loading ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-slate-500">
                  {t('platform.redirects.loading')}
                </td>
              </tr>
            ) : rules.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-slate-500">
                  {t('platform.redirects.empty')}
                </td>
              </tr>
            ) : (
              rules.map((rule) => (
                <tr key={rule.id} className="border-t border-slate-100 dark:border-slate-800">
                  <td className="px-4 py-3">
                    <input
                      type="checkbox"
                      checked={bulkSelection.isSelected(rule.id)}
                      onChange={() => bulkSelection.toggle(rule.id)}
                      aria-label={rule.from}
                    />
                  </td>
                  <td className="px-4 py-3 font-mono text-xs">{rule.from}</td>
                  <td className="px-4 py-3 font-mono text-xs">{rule.to}</td>
                  <td className="px-4 py-3">{rule.status}</td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      disabled={busyId === rule.id}
                      onClick={() => void toggleEnabled(rule)}
                      className={`px-2 py-1 rounded-lg text-xs font-bold ${
                        rule.enabled
                          ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'
                          : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
                      }`}
                    >
                      {rule.enabled ? t('platform.redirects.on') : t('platform.redirects.off')}
                    </button>
                  </td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      disabled={busyId === rule.id}
                      onClick={() => void handleDelete(rule)}
                      className="inline-flex items-center gap-1 text-rose-600 hover:text-rose-700 text-xs font-bold"
                    >
                      <Trash2 className="w-4 h-4" />
                      {t('platform.redirects.delete')}
                    </button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};
