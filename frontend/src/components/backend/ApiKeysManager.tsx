import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Copy, KeyRound, Plus, RefreshCw, RotateCcw, ShieldAlert, Trash2 } from 'lucide-react';
import {
  apiKeysApi,
  type ApiKeyAuditEvent,
  type ApiKeyMetadata,
  type ApiKeysIndexResponse,
} from '../../api/apiKeys';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

const SCOPE_LABEL_KEYS: Record<string, string> = {
  'content:read': 'platform.apiKeys.scopes.contentRead',
  'media:read': 'platform.apiKeys.scopes.mediaRead',
  'settings:read': 'platform.apiKeys.scopes.settingsRead',
  'content:write': 'platform.apiKeys.scopes.contentWrite',
  'media:write': 'platform.apiKeys.scopes.mediaWrite',
  'git:publish': 'platform.apiKeys.scopes.gitPublish',
  'token:issue': 'platform.apiKeys.scopes.tokenIssue',
};

export const ApiKeysManager: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [loading, setLoading] = useState(true);
  const [index, setIndex] = useState<ApiKeysIndexResponse | null>(null);
  const [auditEvents, setAuditEvents] = useState<ApiKeyAuditEvent[]>([]);
  const [showCreate, setShowCreate] = useState(false);
  const [label, setLabel] = useState('');
  const [selectedScopes, setSelectedScopes] = useState<string[]>(['content:read']);
  const [expiresAt, setExpiresAt] = useState('');
  const [copyToken, setCopyToken] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<string | null>(null);
  const [creating, setCreating] = useState(false);

  const scopeGroups = useMemo(
    () => index?.scopeGroups ?? { read: [], write: [], token: [] },
    [index]
  );

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [listData, audit] = await Promise.all([apiKeysApi.list(), apiKeysApi.listAudit()]);
      setIndex(listData);
      setAuditEvents(audit);
    } catch {
      toast.error(t('platform.apiKeys.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [toast, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const toggleScope = (scope: string) => {
    setSelectedScopes((prev) =>
      prev.includes(scope) ? prev.filter((item) => item !== scope) : [...prev, scope]
    );
  };

  const handleCreate = async () => {
    if (!label.trim()) {
      toast.error(t('platform.apiKeys.toast.labelRequired'));
      return;
    }
    if (selectedScopes.length === 0) {
      toast.error(t('platform.apiKeys.toast.scopeRequired'));
      return;
    }

    setCreating(true);
    try {
      const created = await apiKeysApi.create({
        label: label.trim(),
        scopes: selectedScopes,
        expiresAt: expiresAt.trim() !== '' ? expiresAt.trim() : null,
      });
      if (!created) {
        toast.error(t('platform.apiKeys.toast.createFailed'));
        return;
      }
      setCopyToken(created.token);
      setShowCreate(false);
      setLabel('');
      setExpiresAt('');
      setSelectedScopes(['content:read']);
      toast.success(t('platform.apiKeys.toast.created'));
      await load();
    } finally {
      setCreating(false);
    }
  };

  const handleCopy = async (token: string) => {
    try {
      await navigator.clipboard.writeText(token);
      toast.success(t('platform.apiKeys.toast.copied'));
    } catch {
      toast.error(t('platform.apiKeys.toast.copyFailed'));
    }
  };

  const handleRevoke = async (key: ApiKeyMetadata) => {
    if (!window.confirm(t('platform.apiKeys.confirm.revoke', { label: key.label }))) {
      return;
    }
    setBusyId(key.id);
    try {
      const ok = await apiKeysApi.revoke(key.id);
      if (ok) {
        toast.success(t('platform.apiKeys.toast.revoked'));
        await load();
      } else {
        toast.error(t('platform.apiKeys.toast.revokeFailed'));
      }
    } finally {
      setBusyId(null);
    }
  };

  const handleRotate = async (key: ApiKeyMetadata) => {
    if (!window.confirm(t('platform.apiKeys.confirm.rotate', { label: key.label }))) {
      return;
    }
    setBusyId(key.id);
    try {
      const rotated = await apiKeysApi.rotate(key.id);
      if (!rotated) {
        toast.error(t('platform.apiKeys.toast.rotateFailed'));
        return;
      }
      setCopyToken(rotated.token);
      toast.success(t('platform.apiKeys.toast.rotated'));
      await load();
    } finally {
      setBusyId(null);
    }
  };

  const renderScopeLabel = (scope: string) => {
    const key = SCOPE_LABEL_KEYS[scope];
    return key ? t(key) : scope;
  };

  const statusClass = (status: ApiKeyMetadata['status']) => {
    if (status === 'active') return 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40';
    if (status === 'revoked') return 'text-rose-600 bg-rose-50 dark:bg-rose-950/40';
    return 'text-amber-600 bg-amber-50 dark:bg-amber-950/40';
  };

  return (
    <div className="p-6 space-y-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-2">
            <KeyRound className="w-7 h-7 text-indigo-500" />
            {t('platform.apiKeys.title')}
          </h1>
          <p className="text-sm text-slate-500 mt-1 max-w-3xl">{t('platform.apiKeys.subtitle')}</p>
          <p className="text-xs text-amber-700 dark:text-amber-300 mt-2">{t('platform.apiKeys.secretWarning')}</p>
        </div>
        <div className="flex gap-2">
          <button
            type="button"
            onClick={() => void load()}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold"
          >
            <RefreshCw className="w-4 h-4" />
            {t('platform.apiKeys.refresh')}
          </button>
          <button
            type="button"
            onClick={() => setShowCreate(true)}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold"
          >
            <Plus className="w-4 h-4" />
            {t('platform.apiKeys.create')}
          </button>
        </div>
      </div>

      {copyToken && (
        <div className="rounded-2xl border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 p-4 space-y-3">
          <p className="text-sm font-bold text-amber-900 dark:text-amber-100">{t('platform.apiKeys.copyOnceTitle')}</p>
          <code className="block text-xs break-all font-mono bg-white/80 dark:bg-slate-900 p-3 rounded-xl">{copyToken}</code>
          <div className="flex flex-wrap gap-2">
            <button
              type="button"
              onClick={() => void handleCopy(copyToken)}
              className="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-600 text-white text-sm font-bold"
            >
              <Copy className="w-4 h-4" />
              {t('platform.apiKeys.copy')}
            </button>
            <button
              type="button"
              onClick={() => setCopyToken(null)}
              className="px-3 py-2 rounded-lg border border-amber-400 text-sm font-bold"
            >
              {t('platform.apiKeys.dismissCopy')}
            </button>
          </div>
          <pre className="text-xs text-slate-600 dark:text-slate-300 whitespace-pre-wrap">{t('platform.apiKeys.curlExample')}</pre>
        </div>
      )}

      {showCreate && (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 p-5 space-y-4 bg-white dark:bg-slate-900/40">
          <h2 className="text-lg font-bold">{t('platform.apiKeys.createTitle')}</h2>
          <div className="grid gap-4 md:grid-cols-2">
            <label className="block text-sm">
              <span className="font-semibold">{t('platform.apiKeys.fields.label')}</span>
              <input
                value={label}
                onChange={(e) => setLabel(e.target.value)}
                className="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2"
                placeholder={t('platform.apiKeys.fields.labelPlaceholder')}
              />
            </label>
            <label className="block text-sm">
              <span className="font-semibold">{t('platform.apiKeys.fields.expiresAt')}</span>
              <input
                type="datetime-local"
                value={expiresAt}
                onChange={(e) => setExpiresAt(e.target.value)}
                className="mt-1 w-full rounded-xl border border-slate-200 dark:border-slate-700 px-3 py-2"
              />
            </label>
          </div>

          {(['read', 'write', 'token'] as const).map((group) => {
            const scopes = scopeGroups[group];
            if (!scopes?.length) return null;
            return (
              <div key={group}>
                <p className="text-sm font-bold mb-2">{t(`platform.apiKeys.scopeGroups.${group}`)}</p>
                <div className="flex flex-wrap gap-2">
                  {scopes.map((scope) => (
                    <label key={scope} className="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700">
                      <input
                        type="checkbox"
                        checked={selectedScopes.includes(scope)}
                        onChange={() => toggleScope(scope)}
                      />
                      {renderScopeLabel(scope)}
                    </label>
                  ))}
                </div>
              </div>
            );
          })}

          <div className="flex gap-2">
            <button
              type="button"
              disabled={creating}
              onClick={() => void handleCreate()}
              className="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-bold disabled:opacity-60"
            >
              {creating ? t('platform.apiKeys.creating') : t('platform.apiKeys.createSubmit')}
            </button>
            <button
              type="button"
              onClick={() => setShowCreate(false)}
              className="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-bold"
            >
              {t('platform.apiKeys.cancel')}
            </button>
          </div>
        </div>
      )}

      {loading ? (
        <div className="py-12 text-center text-slate-500">{t('platform.apiKeys.loading')}</div>
      ) : (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 dark:bg-slate-900/80 text-left">
              <tr>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.label')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.prefix')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.scopes')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.status')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.created')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.lastUsed')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.columns.actions')}</th>
              </tr>
            </thead>
            <tbody>
              {(index?.keys ?? []).map((key) => (
                <tr key={key.id} className="border-t border-slate-100 dark:border-slate-800 align-top">
                  <td className="px-4 py-3 font-semibold">{key.label}</td>
                  <td className="px-4 py-3 font-mono text-xs">{key.idPrefix}_…</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-1">
                      {key.scopes.map((scope) => (
                        <span key={scope} className="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800">
                          {renderScopeLabel(scope)}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <span className={`text-xs font-bold px-2 py-1 rounded-full ${statusClass(key.status)}`}>
                      {t(`platform.apiKeys.status.${key.status}`)}
                    </span>
                  </td>
                  <td className="px-4 py-3 whitespace-nowrap text-xs">{key.createdAt}</td>
                  <td className="px-4 py-3 whitespace-nowrap text-xs">{key.lastUsedAt ?? '—'}</td>
                  <td className="px-4 py-3">
                    {key.status === 'active' && (
                      <div className="flex gap-2">
                        <button
                          type="button"
                          disabled={busyId === key.id}
                          onClick={() => void handleRotate(key)}
                          className="inline-flex items-center gap-1 text-xs font-bold text-indigo-600"
                        >
                          <RotateCcw className="w-3.5 h-3.5" />
                          {t('platform.apiKeys.rotate')}
                        </button>
                        <button
                          type="button"
                          disabled={busyId === key.id}
                          onClick={() => void handleRevoke(key)}
                          className="inline-flex items-center gap-1 text-xs font-bold text-rose-600"
                        >
                          <Trash2 className="w-3.5 h-3.5" />
                          {t('platform.apiKeys.revoke')}
                        </button>
                      </div>
                    )}
                  </td>
                </tr>
              ))}
              {(index?.keys ?? []).length === 0 && (
                <tr>
                  <td colSpan={7} className="px-4 py-8 text-center text-slate-500">
                    {t('platform.apiKeys.empty')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      <div className="space-y-3">
        <h2 className="text-lg font-bold flex items-center gap-2">
          <ShieldAlert className="w-5 h-5 text-rose-500" />
          {t('platform.apiKeys.auditTitle')}
        </h2>
        <div className="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 dark:bg-slate-900/80 text-left">
              <tr>
                <th className="px-4 py-3">{t('platform.apiKeys.auditColumns.time')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.auditColumns.type')}</th>
                <th className="px-4 py-3">{t('platform.apiKeys.auditColumns.message')}</th>
              </tr>
            </thead>
            <tbody>
              {auditEvents.map((event) => (
                <tr key={event.id} className="border-t border-slate-100 dark:border-slate-800">
                  <td className="px-4 py-3 whitespace-nowrap text-xs">{event.created_at}</td>
                  <td className="px-4 py-3 font-mono text-xs">{event.type}</td>
                  <td className="px-4 py-3">{event.message}</td>
                </tr>
              ))}
              {auditEvents.length === 0 && (
                <tr>
                  <td colSpan={3} className="px-4 py-8 text-center text-slate-500">
                    {t('platform.apiKeys.auditEmpty')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
