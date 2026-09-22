import React, { useCallback, useEffect, useState } from 'react';
import { agentApi, type AgentStatus } from '../../api/agent';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminStatusBadge } from '../admin/AdminStatusBadge';
import { toneFromConnectionOk, toneFromEnabled } from '../../utils/adminStatusKind';

export const AgentSettingsPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [status, setStatus] = useState<AgentStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState(false);
  const [connectionOk, setConnectionOk] = useState<boolean | null>(null);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await agentApi.status();
      if (!res.success || !res.data) {
        setError(res.error || res.message || t('settings.agent.loadFailed'));
        setStatus(null);
        setConnectionOk(null);
        return;
      }
      setStatus(res.data);
      if (res.data.enabled && res.data.provider !== 'none') {
        const probe = await agentApi.testConnection();
        setConnectionOk(Boolean(probe.success && probe.data?.ok));
      } else {
        setConnectionOk(null);
      }
    } catch {
      setError(t('settings.agent.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const runTest = async () => {
    setTesting(true);
    try {
      const res = await agentApi.testConnection();
      const ok = Boolean(res.success && res.data?.ok);
      setConnectionOk(ok);
      if (ok) {
        toast.success(t('settings.agent.testOk'));
      } else {
        toast.error(res.data?.error || res.error || res.message || t('settings.agent.testFailed'));
      }
    } finally {
      setTesting(false);
    }
  };

  const providerActive = Boolean(status?.enabled && status.provider !== 'none');

  return (
    <div className="mt-4 space-y-3 rounded-md border border-admin-border bg-admin-canvas p-4" data-testid="agent-settings-panel">
      <div className="flex flex-wrap items-center justify-end gap-2">
        {status ? (
          <>
            <AdminStatusBadge tone={toneFromEnabled(status.enabled)} />
            {providerActive ? (
              <AdminStatusBadge tone={toneFromConnectionOk(connectionOk)} />
            ) : null}
          </>
        ) : null}
      </div>
      <p className="text-sm text-admin-muted">{t('settings.agent.privacyWarning')}</p>
      <p className="text-sm text-admin-muted">{t('settings.agent.localWarning')}</p>
      <p className="text-sm">
        <a
          href={t('settings.agent.runbookLinkUrl')}
          target="_blank"
          rel="noopener noreferrer"
          className="font-medium text-indigo-600 underline dark:text-indigo-300"
        >
          {t('settings.agent.runbookLinkLabel')}
        </a>
      </p>
      {loading ? (
        <p className="text-sm text-admin-muted">{t('settings.agent.testing')}</p>
      ) : error ? (
        <p className="text-sm text-amber-700 dark:text-amber-200">{error}</p>
      ) : status ? (
        <p className="text-sm text-admin-text">
          {status.provider} · {status.allowedTools.join(', ') || '—'}
        </p>
      ) : null}
      <button
        type="button"
        onClick={() => void runTest()}
        disabled={testing || !providerActive}
        className="rounded-md bg-admin-sidebar-active px-3 py-1.5 text-sm font-semibold text-admin-sidebar-active-text disabled:opacity-60"
      >
        {testing ? t('settings.agent.testing') : t('settings.agent.testConnection')}
      </button>
    </div>
  );
};
