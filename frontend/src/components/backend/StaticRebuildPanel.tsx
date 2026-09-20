import React, { useCallback, useEffect, useState } from 'react';
import { staticSiteApi, type StaticSiteStatus } from '../../api/staticSite';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { useConfirm } from '../../hooks/useConfirm';

export const StaticRebuildPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const confirm = useConfirm();
  const [status, setStatus] = useState<StaticSiteStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [rebuilding, setRebuilding] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await staticSiteApi.status();
      if (!res.success || !res.data) {
        setError(res.error || res.message || t('settings.engine.staticRebuildLoadFailed'));
        setStatus(null);
        return;
      }
      setStatus(res.data);
    } catch {
      setError(t('settings.engine.staticRebuildLoadFailed'));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const runRebuild = async () => {
    const ok = await confirm({
      title: t('settings.engine.staticRebuildConfirmTitle'),
      message: t('settings.engine.staticRebuildConfirmBody'),
      confirmLabel: t('settings.engine.staticRebuildConfirmAction'),
    });
    if (!ok) {
      return;
    }
    setRebuilding(true);
    try {
      const res = await staticSiteApi.rebuildAll();
      if (res.success) {
        toast.success(t('settings.engine.staticRebuildSuccess'));
        await refresh();
      } else {
        toast.error(res.error || res.message || t('settings.engine.staticRebuildFailed'));
      }
    } finally {
      setRebuilding(false);
    }
  };

  if (loading && status === null) {
    return (
      <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">{t('settings.engine.staticRebuildLoading')}</p>
    );
  }

  if (error) {
    return (
      <div className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
        {error}
      </div>
    );
  }

  if (status === null) {
    return null;
  }

  return (
    <div className="mt-4 rounded-md border border-gray-200 p-3 dark:border-gray-700" data-testid="static-rebuild-panel">
      <h6 className="text-sm font-semibold text-gray-900 dark:text-white">{t('settings.engine.staticRebuildTitle')}</h6>
      <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.staticRebuildIntro')}</p>
      <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
        <div>
          <dt className="text-gray-500 dark:text-gray-400">{t('settings.engine.staticRebuildMode')}</dt>
          <dd className="font-medium text-gray-800 dark:text-gray-100">{status.renderMode}</dd>
        </div>
        <div>
          <dt className="text-gray-500 dark:text-gray-400">{t('settings.engine.staticRebuildCounts')}</dt>
          <dd className="font-medium text-gray-800 dark:text-gray-100">
            {status.pageCount} / {status.articleCount}
          </dd>
        </div>
        <div className="sm:col-span-2">
          <dt className="text-gray-500 dark:text-gray-400">{t('settings.engine.staticRebuildPublic')}</dt>
          <dd className="font-medium text-gray-800 dark:text-gray-100">
            {status.publicServe
              ? t('settings.engine.staticRebuildPublicOn', { prefix: status.publicPrefix })
              : t('settings.engine.staticRebuildPublicOff')}
          </dd>
        </div>
      </dl>
      <button
        type="button"
        className="btn btn-secondary mt-3 text-sm"
        disabled={rebuilding || !status.writable}
        onClick={() => void runRebuild()}
      >
        {rebuilding ? t('settings.engine.staticRebuildWorking') : t('settings.engine.staticRebuildRun')}
      </button>
    </div>
  );
};
