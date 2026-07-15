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

export const GitHubSyncPanel: React.FC = () => {
  const { error: showError, success: showSuccess } = useToast();
  const [status, setStatus] = useState<GitHubStatus | null>(null);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('Content sync');

  const load = useCallback(async () => {
    setLoading(true);
    try {
      setStatus(await getGitHubStatus());
    } catch {
      showError('Failed to load GitHub status.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

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
        showSuccess(`${action} completed.`);
      } else {
        showError(result.error ?? `${action} failed.`);
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
        showSuccess('Auto-sync updated.');
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
          GitHub Sync
        </h1>
        <p className="text-sm text-gray-500 mt-1">Export/import content via GitHub REST API.</p>
      </div>

      <div className="card">
        <div className="card-body space-y-3">
          <p>
            <span className="text-gray-500">Repository:</span>{' '}
            <code>{status?.repo || '—'}</code>
          </p>
          <p>
            <span className="text-gray-500">Branch:</span> {status?.branch || 'main'}
          </p>
          <p>
            <span className="text-gray-500">Configured:</span>{' '}
            {status?.configured ? 'Yes' : 'No — set GITHUB_* in .env'}
          </p>
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={Boolean(status?.auto_sync)}
              disabled={busy || !status?.configured}
              onChange={() => void toggleAutoSync()}
            />
            Auto sync enabled
          </label>
        </div>
      </div>

      <div className="card">
        <div className="card-body space-y-4">
          <input
            className="form-input"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            placeholder="Commit message"
          />
          <div className="flex flex-wrap gap-3">
            <button type="button" className="btn btn-primary" disabled={busy || !status?.configured} onClick={() => void run('export')}>
              <Upload className="w-4 h-4 inline mr-1" />
              Export
            </button>
            <button type="button" className="btn btn-secondary" disabled={busy || !status?.configured} onClick={() => void run('import')}>
              <Download className="w-4 h-4 inline mr-1" />
              Import
            </button>
            <button type="button" className="btn btn-secondary" disabled={busy || !status?.configured} onClick={() => void run('sync')}>
              <RefreshCw className="w-4 h-4 inline mr-1" />
              Sync
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GitHubSyncPanel;
