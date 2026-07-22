// frontend/src/components/backend/GitHubSyncPanel.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { GitBranch, RefreshCw, Upload, Download } from 'lucide-react';
import {
  exportToGitHub,
  getGitHubStatus,
  GitHubStatus,
  importFromGitHub,
  setGitHubAutoSync,
  syncGitHub,
} from '../../api/github';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';

export const GitHubSyncPanel: React.FC = () => {
  const { t } = useI18n();
  const { error: showError, success: showSuccess } = useToast();
  const [status, setStatus] = useState<GitHubStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState(() => t('platform.github.defaultCommitMessage'));

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setStatus(await getGitHubStatus());
    } catch {
      showError(t('platform.github.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
  }, [showError, t]);

  useEffect(() => {
    void load();
  }, [load]);

  const actionLabel = (action: 'export' | 'import' | 'sync') => t(`platform.github.${action}`);

  const run = async (action: 'export' | 'import' | 'sync') => {
    setBusy(true);
    try {
      const result =
        action === 'export'
          ? await exportToGitHub(message)
          : action === 'import'
            ? await importFromGitHub()
            : await syncGitHub(message);
      if (result.success) {
        showSuccess(t('platform.github.toast.actionCompleted', { action: actionLabel(action) }));
      } else {
        showError(result.error ?? t('platform.github.toast.actionFailed', { action: actionLabel(action) }));
      }
      await load();
    } finally {
      setBusy(false);
    }
  };

  const toggleAutoSync = async () => {
    if (!status) return;
    setBusy(true);
    try {
      const ok = await setGitHubAutoSync(!status.auto_sync);
      if (ok) {
        showSuccess(t('platform.github.toast.autoSyncUpdated'));
        await load();
      }
    } finally {
      setBusy(false);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center py-16">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold flex items-center gap-2">
          <GitBranch className="w-6 h-6 text-indigo-500" />
          {t('platform.github.title')}
        </h1>
        <p className="text-sm text-gray-500 mt-1">{t('platform.github.subtitle')}</p>
      </div>

      <div className="card">
        <div className="card-body space-y-3">
          <p>
            <span className="text-gray-500">{t('platform.github.repository')}</span>{' '}
            <code>{status?.repo || '—'}</code>
          </p>
          <p>
            <span className="text-gray-500">{t('platform.github.branch')}</span> {status?.branch || 'main'}
          </p>
          <p>
            <span className="text-gray-500">{t('platform.github.configured')}</span>{' '}
            {status?.configured ? t('platform.github.configuredYes') : t('platform.github.configuredNo')}
          </p>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={Boolean(status?.auto_sync)}
              disabled={busy || !status?.configured}
              onChange={() => void toggleAutoSync()}
            />
            {t('platform.github.autoSync')}
          </label>
        </div>
      </div>

      <div className="card">
        <div className="card-body space-y-4">
          <input
            className="form-input"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            placeholder={t('platform.github.commitPlaceholder')}
          />
          <div className="flex flex-wrap gap-3">
            <button type="button" className="btn btn-primary" disabled={busy || !status?.configured} onClick={() => void run('export')}>
              <Upload className="w-4 h-4 inline mr-1" />
              {t('platform.github.export')}
            </button>
            <button type="button" className="btn btn-secondary" disabled={busy || !status?.configured} onClick={() => void run('import')}>
              <Download className="w-4 h-4 inline mr-1" />
              {t('platform.github.import')}
            </button>
            <button type="button" className="btn btn-secondary" disabled={busy || !status?.configured} onClick={() => void run('sync')}>
              <RefreshCw className="w-4 h-4 inline mr-1" />
              {t('platform.github.sync')}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GitHubSyncPanel;
