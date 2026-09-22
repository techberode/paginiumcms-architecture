import React, { useCallback, useEffect, useState } from 'react';
import { contentTranslationsApi, type ContentTranslationStatus } from '../../api/contentTranslations';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { AdminStatusBadge } from '../admin/AdminStatusBadge';
import { toneFromConnectionOk, toneFromEnabled } from '../../utils/adminStatusKind';

export const TranslationSettingsPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const [status, setStatus] = useState<ContentTranslationStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [testing, setTesting] = useState(false);
  const [connectionOk, setConnectionOk] = useState<boolean | null>(null);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await contentTranslationsApi.status();
      if (!res.success || !res.data) {
        setError(res.error || res.message || t('settings.translation.loadFailed'));
        setStatus(null);
        setConnectionOk(null);
        return;
      }
      setStatus(res.data);
      if (res.data.enabled && res.data.provider !== 'none') {
        const probe = await contentTranslationsApi.testConnection();
        setConnectionOk(Boolean(probe.success && probe.data?.ok));
      } else {
        setConnectionOk(null);
      }
    } catch {
      setError(t('settings.translation.loadFailed'));
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
      const res = await contentTranslationsApi.testConnection();
      const ok = Boolean(res.success && res.data?.ok);
      setConnectionOk(ok);
      if (ok) {
        toast.success(t('settings.translation.testOk'));
      } else {
        toast.error(res.data?.error || res.error || res.message || t('settings.translation.testFailed'));
      }
    } finally {
      setTesting(false);
    }
  };

  const quotaLabel = status
    ? status.quota.limit === 0
      ? t('settings.translation.quotaUnlimited')
      : t('settings.translation.quotaUsed', {
          used: String(status.quota.used),
          limit: String(status.quota.limit),
        })
    : '';

  const providerActive = Boolean(status?.enabled && status.provider !== 'none');

  return (
    <div className="mt-4 space-y-3 rounded-md border border-admin-border bg-admin-canvas p-4" data-testid="translation-settings-panel">
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
      <p className="text-sm text-admin-muted">{t('settings.translation.instanceRequired')}</p>
      <p className="text-sm text-admin-muted">{t('settings.translation.nginxProxyHint')}</p>
      <p className="text-sm">
        <a
          href={t('settings.translation.runbookLinkUrl')}
          target="_blank"
          rel="noopener noreferrer"
          className="font-medium text-indigo-600 underline dark:text-indigo-300"
        >
          {t('settings.translation.runbookLinkLabel')}
        </a>
      </p>
      <p className="text-sm text-admin-muted">{t('settings.translation.cloudWarning')}</p>
      <p className="text-sm text-admin-muted">{t('settings.translation.privacyWarning')}</p>
      {loading ? (
        <p className="text-sm text-admin-muted">{t('settings.translation.testing')}</p>
      ) : error ? (
        <p className="text-sm text-amber-700 dark:text-amber-200">{error}</p>
      ) : (
        <p className="text-sm text-admin-text">
          {t('settings.translation.quotaLabel')}: {quotaLabel}
        </p>
      )}
      <button
        type="button"
        onClick={() => void runTest()}
        disabled={testing || !providerActive}
        className="rounded-md bg-admin-sidebar-active px-3 py-1.5 text-sm font-semibold text-admin-sidebar-active-text disabled:opacity-60"
      >
        {testing ? t('settings.translation.testing') : t('settings.translation.testConnection')}
      </button>
    </div>
  );
};
