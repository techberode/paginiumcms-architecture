// frontend/src/components/backend/SystemUpdateView.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { ArrowUpCircle, GitBranch, Play, RefreshCw, Settings } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useSettings } from '../../hooks/useSettings';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import {
  checkSystemUpdate,
  getSystemUpdateStatus,
  runSystemUpdate,
  type SystemUpdateCheckResult,
  type SystemUpdateRemote,
  type SystemUpdateStatus,
} from '../../api/systemUpdate';
import { settingsGroupPath } from '../../utils/adminDeepLinks';

export const SystemUpdateView: React.FC = () => {
  const { t } = useI18n();
  const { user } = useAuth();
  const { settings } = useSettings();
  const { success, error: toastError, warning } = useToast();
  const [data, setData] = useState<SystemUpdateStatus | null>(null);
  const [remoteCheck, setRemoteCheck] = useState<SystemUpdateCheckResult | null>(null);
  const [loading, setLoading] = useState(true);
  const [checking, setChecking] = useState(false);
  const [deploying, setDeploying] = useState(false);
  const [ref, setRef] = useState('');

  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const isDemoInstance = settings?.demo?.enabled === true;

  const applyDefaultRef = (status: SystemUpdateStatus | null, remote?: SystemUpdateRemote | null) => {
    const tag = remote?.latest_release_tag?.trim() ?? '';
    if (tag !== '') {
      setRef(tag);
      return;
    }
    const cfg = status?.config;
    const branch = cfg?.defaultBranch?.trim() || 'main';
    if (cfg?.allowDeployMain) {
      setRef(`origin/${branch}`);
    }
  };

  const updateStatus = remoteCheck?.update?.status;
  const latestTag =
    remoteCheck?.update?.latest_tag ??
    remoteCheck?.remote.latest_release_tag ??
    null;

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const status = await getSystemUpdateStatus();
      setData(status);
      applyDefaultRef(status, null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  if (!isSuperAdmin) {
    return (
      <div className="p-6 text-sm text-slate-600">
        {t('platform.systemUpdate.superAdminOnly')}
      </div>
    );
  }

  if (isDemoInstance) {
    return (
      <div className="p-6 text-sm text-amber-800 bg-amber-50 rounded-xl border border-amber-200">
        {t('platform.systemUpdate.demoDisabled')}
      </div>
    );
  }

  const handleCheck = async () => {
    setChecking(true);
    try {
      const result = await checkSystemUpdate();
      if (!result) {
        toastError(t('platform.systemUpdate.toast.checkFailed'));
        return;
      }
      setRemoteCheck(result);
      applyDefaultRef(data, result.remote);
      const newLatestTag =
        result.update?.latest_tag ?? result.remote.latest_release_tag ?? null;
      if (result.update?.status === 'current') {
        success(t('platform.systemUpdate.toast.checkCurrent'));
      } else if (result.update?.status === 'update_available') {
        success(t('platform.systemUpdate.toast.checkUpdateAvailable', { version: newLatestTag ?? '?' }));
      } else {
        success(t('platform.systemUpdate.toast.checkOk'));
      }
    } finally {
      setChecking(false);
    }
  };

  const handleDeploy = async (overrideRef?: string) => {
    if (!data?.config?.deployEnabled) {
      warning(t('platform.systemUpdate.toast.deployDisabled'));
      return;
    }
    const deployRef = (overrideRef ?? ref).trim();
    if (deployRef === '') {
      toastError(t('platform.systemUpdate.toast.refRequired'));
      return;
    }
    setDeploying(true);
    try {
      const { data: result, error } = await runSystemUpdate(deployRef);
      if (!result) {
        toastError(error ?? t('platform.systemUpdate.toast.deployFailed'));
        return;
      }
      success(t('platform.systemUpdate.toast.deployStarted'));
      await load();
    } finally {
      setDeploying(false);
    }
  };

  const handleDeployLatestTag = async () => {
    const tag = latestTag?.trim() ?? '';
    if (tag === '') {
      toastError(t('platform.systemUpdate.toast.refRequired'));
      return;
    }
    if (
      !window.confirm(
        t('platform.systemUpdate.deployLatestConfirm', { tag })
      )
    ) {
      return;
    }
    setRef(tag);
    await handleDeploy(tag);
  };

  const compareCommits = remoteCheck?.remote.compare?.commits ?? [];
  const canDeployLatestTag =
    Boolean(latestTag) &&
    updateStatus === 'update_available' &&
    data?.config?.deployEnabled === true &&
    data?.job_registered === true;

  const webhookPath = data?.webhook?.path ?? '/api/webhooks/github/release';
  const webhookUrl =
    typeof window !== 'undefined' ? `${window.location.origin}${webhookPath}` : webhookPath;

  return (
    <div className="space-y-6 p-4 md:p-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <ArrowUpCircle className="w-7 h-7 text-indigo-600" />
            {t('platform.systemUpdate.title')}
          </h1>
          <p className="text-sm text-slate-600 mt-1">{t('platform.systemUpdate.subtitle')}</p>
        </div>
        <div className="flex gap-2">
          <button type="button" onClick={() => void load()} className="btn-secondary inline-flex items-center gap-2">
            <RefreshCw className="w-4 h-4" />
            {t('platform.systemUpdate.refresh')}
          </button>
          <Link to={settingsGroupPath('systemUpdate')} className="btn-secondary inline-flex items-center gap-2">
            <Settings className="w-4 h-4" />
            {t('platform.systemUpdate.settingsLink')}
          </Link>
        </div>
      </div>

      {loading ? (
        <p className="text-sm text-slate-500">{t('common.loading')}</p>
      ) : (
        <>
          <div className="grid md:grid-cols-2 gap-4">
            <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
              <h2 className="font-semibold text-slate-800 mb-2">{t('platform.systemUpdate.current')}</h2>
              <dl className="text-sm space-y-1">
                <div><dt className="inline text-slate-500">{t('platform.systemUpdate.appVersion')}: </dt><dd className="inline font-mono">{data?.app_version}</dd></div>
                <div><dt className="inline text-slate-500">{t('platform.systemUpdate.gitDescribe')}: </dt><dd className="inline font-mono">{data?.git?.describe ?? '—'}</dd></div>
                <div><dt className="inline text-slate-500">{t('platform.systemUpdate.gitCommit')}: </dt><dd className="inline font-mono">{data?.git?.commit ?? '—'}</dd></div>
                <div><dt className="inline text-slate-500">{t('platform.systemUpdate.deployEnabled')}: </dt><dd className="inline">{data?.config?.deployEnabled ? t('platform.scheduler.yes') : t('platform.scheduler.no')}</dd></div>
              </dl>
            </div>
            <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
              <h2 className="font-semibold text-slate-800 mb-2">{t('platform.systemUpdate.remote')}</h2>
              {remoteCheck ? (
                <>
                  {updateStatus === 'current' && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                      {t('platform.systemUpdate.versionCurrent')}
                    </div>
                  )}
                  {updateStatus === 'update_available' && (
                    <div className="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 space-y-1">
                      <p className="font-medium">
                        {t('platform.systemUpdate.versionUpdateAvailable', {
                          version: latestTag ?? remoteCheck.update?.latest_version ?? '?',
                        })}
                      </p>
                      {remoteCheck.release_url && (
                        <a
                          href={remoteCheck.release_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-indigo-700 underline text-xs"
                        >
                          {t('platform.systemUpdate.releaseLink')}
                        </a>
                      )}
                    </div>
                  )}
                  {updateStatus === 'unknown' && (
                    <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                      {t('platform.systemUpdate.versionUnknown')}
                    </div>
                  )}
                  {updateStatus === 'update_available' && remoteCheck.release_notes && (
                    <div>
                      <h3 className="text-sm font-medium text-slate-800 mb-2">
                        {t('platform.systemUpdate.releaseNotes')}
                      </h3>
                      <pre className="text-xs bg-slate-50 p-3 rounded-lg overflow-auto max-h-64 whitespace-pre-wrap">
                        {remoteCheck.release_notes}
                      </pre>
                    </div>
                  )}
                  <dl className="text-xs text-slate-600 space-y-1">
                    {typeof remoteCheck.remote.latest_release_tag === 'string' &&
                      remoteCheck.remote.latest_release_tag !== '' && (
                      <div>
                        <dt className="inline text-slate-500">{t('platform.systemUpdate.latestTag')}: </dt>
                        <dd className="inline font-mono">{remoteCheck.remote.latest_release_tag}</dd>
                      </div>
                    )}
                    {remoteCheck.remote.compare?.behind_by !== undefined && (
                        <div>
                          <dt className="inline text-slate-500">{t('platform.systemUpdate.commitsBehind')}: </dt>
                          <dd className="inline font-mono">{remoteCheck.remote.compare.behind_by}</dd>
                        </div>
                      )}
                  </dl>
                  <div className="space-y-2">
                      <h3 className="text-sm font-medium text-slate-800">
                        {t('platform.systemUpdate.commitsTitle')}
                      </h3>
                      {compareCommits.length === 0 ? (
                        <p className="text-xs text-slate-500">{t('platform.systemUpdate.commitsEmpty')}</p>
                      ) : (
                        <>
                          <div className="overflow-auto max-h-72 rounded-lg border border-slate-200">
                            <table className="min-w-full text-xs">
                              <thead className="bg-slate-50 text-slate-600">
                                <tr>
                                  <th className="px-3 py-2 text-left font-medium">SHA</th>
                                  <th className="px-3 py-2 text-left font-medium">
                                    {t('platform.systemUpdate.commitMessage')}
                                  </th>
                                  <th className="px-3 py-2 text-left font-medium hidden sm:table-cell">
                                    {t('platform.systemUpdate.commitAuthor')}
                                  </th>
                                  <th className="px-3 py-2 text-left font-medium hidden md:table-cell">
                                    {t('platform.systemUpdate.commitDate')}
                                  </th>
                                </tr>
                              </thead>
                              <tbody className="divide-y divide-slate-100">
                                {compareCommits.map((commit) => (
                                  <tr key={commit.sha_full ?? commit.sha} className="bg-white">
                                    <td className="px-3 py-2 font-mono whitespace-nowrap">
                                      {commit.url ? (
                                        <a
                                          href={commit.url}
                                          target="_blank"
                                          rel="noopener noreferrer"
                                          className="text-indigo-700 hover:underline"
                                        >
                                          {commit.sha}
                                        </a>
                                      ) : (
                                        commit.sha
                                      )}
                                    </td>
                                    <td className="px-3 py-2 text-slate-800">{commit.message}</td>
                                    <td className="px-3 py-2 text-slate-600 hidden sm:table-cell">
                                      {commit.author ?? '—'}
                                    </td>
                                    <td className="px-3 py-2 text-slate-500 hidden md:table-cell whitespace-nowrap">
                                      {commit.date ? new Date(commit.date).toLocaleString() : '—'}
                                    </td>
                                  </tr>
                                ))}
                              </tbody>
                            </table>
                          </div>
                          {remoteCheck.remote.compare?.commits_truncated && (
                            <p className="text-xs text-slate-500">
                              {t('platform.systemUpdate.commitsTruncated', {
                                shown: String(compareCommits.length),
                                total: String(remoteCheck.remote.compare?.total_commits ?? compareCommits.length),
                              })}
                            </p>
                          )}
                        </>
                      )}
                    </div>
                </>
              ) : (
                <p className="text-sm text-slate-500">{t('platform.systemUpdate.remoteHint')}</p>
              )}
              <button type="button" disabled={checking} onClick={() => void handleCheck()} className="btn-secondary inline-flex items-center gap-2">
                <GitBranch className="w-4 h-4" />
                {checking ? t('platform.systemUpdate.checking') : t('platform.systemUpdate.checkRemote')}
              </button>
            </div>
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 className="font-semibold text-slate-800">{t('platform.systemUpdate.deployTitle')}</h2>
            {updateStatus === 'current' && (
              <p className="text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                {t('platform.systemUpdate.deployUpToDate')}
              </p>
            )}
            <label className="block text-sm text-slate-600">
              {t('platform.systemUpdate.refLabel')}
              <input
                className="mt-1 w-full max-w-md rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm"
                value={ref}
                onChange={(e) => setRef(e.target.value)}
                placeholder={
                  data?.config?.allowDeployMain
                    ? 'v2.1.0-beta.12 or origin/main'
                    : 'v2.1.0-beta.12'
                }
              />
            </label>
            {!data?.config?.allowDeployMain && (
              <p className="text-xs text-slate-500">{t('platform.systemUpdate.refTagOnlyHint')}</p>
            )}
            <div className="flex flex-wrap gap-2">
              {canDeployLatestTag && latestTag ? (
                <button
                  type="button"
                  disabled={deploying}
                  onClick={() => void handleDeployLatestTag()}
                  className="btn-primary inline-flex items-center gap-2"
                >
                  <Play className="w-4 h-4" />
                  {deploying
                    ? t('platform.systemUpdate.deploying')
                    : t('platform.systemUpdate.deployLatestTag', { tag: latestTag })}
                </button>
              ) : null}
              <button
                type="button"
                disabled={deploying || !data?.job_registered}
                onClick={() => void handleDeploy()}
                className={canDeployLatestTag ? 'btn-secondary inline-flex items-center gap-2' : 'btn-primary inline-flex items-center gap-2'}
              >
                <Play className="w-4 h-4" />
                {deploying ? t('platform.systemUpdate.deploying') : t('platform.systemUpdate.deployNow')}
              </button>
            </div>
            {!data?.job_registered && (
              <p className="text-xs text-amber-700">{t('platform.systemUpdate.jobMissing')}</p>
            )}
          </div>

          <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 className="font-semibold text-slate-800">{t('platform.systemUpdate.webhookTitle')}</h2>
            <p className="text-sm text-slate-600">{t('platform.systemUpdate.webhookHint')}</p>
            <dl className="text-sm space-y-2">
              <div>
                <dt className="text-slate-500">{t('platform.systemUpdate.webhookUrl')}</dt>
                <dd className="mt-1 font-mono text-xs break-all rounded-lg bg-slate-50 px-3 py-2">{webhookUrl}</dd>
              </div>
              <div>
                <dt className="inline text-slate-500">{t('platform.systemUpdate.webhookEnabled')}: </dt>
                <dd className="inline">
                  {data?.webhook?.webhook_deploy_enabled === true
                    ? t('platform.scheduler.yes')
                    : t('platform.scheduler.no')}
                </dd>
              </div>
              <div>
                <dt className="text-slate-500">{t('platform.systemUpdate.webhookEvents')}</dt>
              </div>
            </dl>
            {data?.webhook?.secret_configured ? (
              <p className="text-xs text-emerald-700">{t('platform.systemUpdate.webhookSecretConfigured')}</p>
            ) : (
              <p className="text-xs text-amber-700">{t('platform.systemUpdate.webhookSecretMissing')}</p>
            )}
          </div>

          {data?.recent_runs && data.recent_runs.length > 0 && (
            <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
              <h2 className="font-semibold text-slate-800 mb-2">{t('platform.systemUpdate.recentRuns')}</h2>
              <pre className="text-xs bg-slate-50 p-3 rounded-lg overflow-auto max-h-64">{JSON.stringify(data.recent_runs, null, 2)}</pre>
            </div>
          )}
        </>
      )}
    </div>
  );
};
