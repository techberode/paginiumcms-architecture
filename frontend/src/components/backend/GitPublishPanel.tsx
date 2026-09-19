import React, { useCallback, useEffect, useState } from 'react';
import { gitApi, type GitPublishPreview, type GitPublishStatus } from '../../api/git';
import { useI18n } from '../../context/I18nContext';
import { useToast } from '../../hooks/useToast';
import { useConfirm } from '../../hooks/useConfirm';

export const GitPublishPanel: React.FC = () => {
  const { t } = useI18n();
  const toast = useToast();
  const confirm = useConfirm();
  const [status, setStatus] = useState<GitPublishStatus | null>(null);
  const [preview, setPreview] = useState<GitPublishPreview | null>(null);
  const [loading, setLoading] = useState(true);
  const [publishing, setPublishing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [statusRes, previewRes] = await Promise.all([gitApi.status(), gitApi.preview()]);
      if (!statusRes.success || !statusRes.data) {
        setError(statusRes.error || statusRes.message || t('settings.engine.gitPublishLoadFailed'));
        setStatus(null);
        setPreview(null);
        return;
      }
      setStatus(statusRes.data);
      setPreview(previewRes.success && previewRes.data ? previewRes.data : null);
    } catch {
      setError(t('settings.engine.gitPublishLoadFailed'));
    } finally {
      setLoading(false);
    }
  }, [t]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const runPublish = async () => {
    if (!preview || preview.pathCount === 0) {
      return;
    }
    const ok = await confirm({
      title: t('settings.engine.gitPublishConfirmTitle'),
      message: t('settings.engine.gitPublishConfirmBody', { count: preview.pathCount }),
      confirmLabel: t('settings.engine.gitPublishConfirmAction'),
    });
    if (!ok) {
      return;
    }
    setPublishing(true);
    try {
      const res = await gitApi.publish();
      if (res.success) {
        toast.success(t('settings.engine.gitPublishSuccess'));
        await refresh();
      } else {
        toast.error(res.error || res.message || t('settings.engine.gitPublishFailed'));
      }
    } finally {
      setPublishing(false);
    }
  };

  if (loading && status === null) {
    return (
      <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">{t('settings.engine.gitPublishLoading')}</p>
    );
  }

  if (error) {
    return (
      <div className="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100">
        {error}
      </div>
    );
  }

  if (!status?.enabled || status.strategy === 'disabled') {
    return null;
  }

  const queued = status.strategy === 'queued';

  return (
    <div className="mt-4 rounded-md border border-gray-200 p-3 dark:border-gray-700" data-testid="git-publish-panel">
      <h6 className="text-sm font-semibold text-gray-900 dark:text-white">{t('settings.engine.gitPublishTitle')}</h6>
      <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.gitPublishIntro')}</p>
      <dl className="mt-3 grid gap-2 text-sm sm:grid-cols-2">
        <div>
          <dt className="text-gray-500 dark:text-gray-400">{t('settings.engine.gitPublishDriver')}</dt>
          <dd className="font-medium text-gray-800 dark:text-gray-100">{status.publisher}</dd>
        </div>
        <div>
          <dt className="text-gray-500 dark:text-gray-400">{t('settings.engine.gitPublishPending')}</dt>
          <dd className="font-medium text-gray-800 dark:text-gray-100">{status.pendingCount}</dd>
        </div>
      </dl>
      {preview && preview.pathCount > 0 ? (
        <ul className="mt-3 max-h-40 overflow-auto text-xs text-gray-600 dark:text-gray-300">
          {preview.paths.map((path) => (
            <li key={path} className="truncate font-mono">
              {path}
            </li>
          ))}
        </ul>
      ) : (
        <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.gitPublishEmpty')}</p>
      )}
      {queued ? (
        <button
          type="button"
          className="btn btn-primary mt-3 text-sm"
          disabled={publishing || !preview || preview.pathCount === 0}
          onClick={() => void runPublish()}
        >
          {publishing ? t('settings.engine.gitPublishWorking') : t('settings.engine.gitPublishRun')}
        </button>
      ) : (
        <p className="mt-3 text-xs text-gray-500 dark:text-gray-400">{t('settings.engine.gitPublishImmediateHint')}</p>
      )}
    </div>
  );
};
