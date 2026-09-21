import React from 'react';
import { Link } from 'react-router-dom';
import { ArrowUpCircle, Play, RefreshCw, Settings, X } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useSettings } from '../../hooks/useSettings';
import { useToast } from '../../hooks/useToast';
import { useI18n } from '../../context/I18nContext';
import { useAdminConfirm } from '../../hooks/useAdminConfirm';
import { useSystemUpdateFlow } from '../../hooks/useSystemUpdateFlow';
import { settingsGroupPath } from '../../utils/adminDeepLinks';
import { DeployBlockersList } from './DeployBlockersList';
import {
  isSystemUpdateCacheFresh,
  normalizeRemoteCheckIntervalHours,
} from '../../utils/systemUpdateCheckCache';

export const SystemUpdateBanner: React.FC = () => {
  const { t } = useI18n();
  const confirmDestructive = useAdminConfirm();
  const { user } = useAuth();
  const { settings } = useSettings();
  const { success, error: toastError, warning } = useToast();
  const [dismissed, setDismissed] = React.useState(false);

  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const isDemoInstance = settings.demo?.enabled === true;
  const enabled = isSuperAdmin && !isDemoInstance;
  const intervalHours = normalizeRemoteCheckIntervalHours(
    settings.systemUpdate?.remoteCheckIntervalHours
  );

  const {
    check,
    readiness,
    checking,
    deploying,
    latestTag,
    updateStatus,
    canDeploy,
    lastCheckedAt,
    refreshCheck,
    deployLatest,
  } = useSystemUpdateFlow(enabled);

  if (!enabled || dismissed) {
    return null;
  }

  const showLoading = checking && !check;
  const manualOnly = intervalHours === 0;
  const cacheStale =
    lastCheckedAt !== null &&
    intervalHours > 0 &&
    !isSystemUpdateCacheFresh(lastCheckedAt, intervalHours);
  const showCurrent =
    check !== null && updateStatus === 'current' && !checking;
  const showUpdateAvailable =
    check !== null && updateStatus === 'update_available' && Boolean(latestTag);
  const showUnknown = check !== null && updateStatus === 'unknown' && !checking;

  const handleCheck = async () => {
    const result = await refreshCheck({ force: true });
    if (!result.data) {
      toastError(result.error ?? t('platform.systemUpdate.toast.checkFailed'));
      return;
    }
    if (result.data.update?.status === 'current') {
      success(t('platform.systemUpdate.toast.checkCurrent'));
    } else if (result.data.update?.status === 'update_available') {
      success(
        t('platform.systemUpdate.toast.checkUpdateAvailable', {
          version: result.data.update.latest_tag ?? result.data.remote.latest_release_tag ?? '?',
        })
      );
    } else {
      success(t('platform.systemUpdate.toast.checkOk'));
    }
  };

  const handleDeploy = async () => {
    const tag = latestTag?.trim() ?? '';
    if (tag === '') {
      toastError(t('platform.systemUpdate.toast.refRequired'));
      return;
    }
    if (!readiness?.ready) {
      warning(t('platform.systemUpdate.toast.deployNotReady'));
      return;
    }
    if (
      !(await confirmDestructive(
        t('platform.systemUpdate.backupBeforeDeployConfirm', { ref: tag })
      ))
    ) {
      return;
    }
    const outcome = await deployLatest(tag);
    if (!outcome.ok) {
      toastError(outcome.error ?? t('platform.systemUpdate.toast.deployFailed'));
      return;
    }
    if (outcome.skipped) {
      warning(t('platform.systemUpdate.toast.deploySkipped'));
      return;
    }
    success(t('platform.systemUpdate.toast.deployStarted'));
  };

  const title = showUpdateAvailable
    ? t('dashboard.updateBanner.title')
    : showCurrent
      ? t('dashboard.updateBanner.titleCurrent')
      : showUnknown
        ? t('dashboard.updateBanner.titleUnknown')
        : t('dashboard.updateBanner.titleCheck');

  const message = showUpdateAvailable
    ? t('dashboard.updateBanner.message', { version: latestTag ?? '?' })
    : showCurrent
      ? t('dashboard.updateBanner.messageCurrent')
      : showUnknown
        ? t('dashboard.updateBanner.messageUnknown')
        : t('dashboard.updateBanner.messageCheck');

  return (
    <div className="rounded-2xl border border-indigo-200 dark:border-indigo-900/60 bg-indigo-50 dark:bg-indigo-950/40 p-4 sm:p-5 flex flex-col gap-4">
      <div className="flex items-start gap-3">
        <div className="p-2 rounded-xl bg-indigo-600 text-white shrink-0">
          <ArrowUpCircle className="w-5 h-5" />
        </div>
        <div className="flex-1 min-w-0 space-y-2">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <p className="font-bold text-indigo-950 dark:text-indigo-100">{title}</p>
              <p className="text-sm text-indigo-900/80 dark:text-indigo-200/80 mt-1">{message}</p>
              {(manualOnly || check === null) && !checking ? (
                <p className="text-xs text-indigo-800/70 dark:text-indigo-200/70 mt-1">
                  {t('dashboard.updateBanner.manualOnlyHint')}
                </p>
              ) : null}
              {cacheStale && !checking ? (
                <p className="text-xs text-amber-800/80 dark:text-amber-200/80 mt-1">
                  {t('dashboard.updateBanner.staleHint')}
                </p>
              ) : null}
              {lastCheckedAt !== null && check !== null ? (
                <p className="text-xs text-indigo-800/60 dark:text-indigo-200/60 mt-1">
                  {t('dashboard.updateBanner.lastChecked', {
                    time: new Date(lastCheckedAt).toLocaleString(),
                  })}
                </p>
              ) : null}
            </div>
            <button
              type="button"
              onClick={() => setDismissed(true)}
              className="p-2 rounded-xl text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100/70 dark:hover:bg-indigo-900/40 shrink-0"
              aria-label={t('dashboard.updateBanner.dismiss')}
            >
              <X className="w-4 h-4" />
            </button>
          </div>

          {showLoading || checking ? (
            <p className="text-xs text-indigo-800/70 dark:text-indigo-200/70">
              {t('dashboard.updateBanner.checking')}
            </p>
          ) : null}

          {!readiness?.ready && showUpdateAvailable ? (
            <DeployBlockersList readiness={readiness} compact />
          ) : null}
        </div>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <button
          type="button"
          onClick={() => void handleCheck()}
          disabled={checking || deploying}
          className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 text-sm font-semibold hover:bg-indigo-100/70 dark:hover:bg-indigo-900/40 disabled:opacity-60"
        >
          <RefreshCw className={`w-4 h-4 ${checking ? 'animate-spin' : ''}`} />
          {checking ? t('dashboard.updateBanner.checking') : t('dashboard.updateBanner.refresh')}
        </button>

        {showUpdateAvailable && canDeploy && latestTag ? (
          <button
            type="button"
            onClick={() => void handleDeploy()}
            disabled={deploying || checking}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold shadow-sm disabled:opacity-60"
          >
            <Play className="w-4 h-4" />
            {deploying
              ? t('dashboard.updateBanner.deploying')
              : t('dashboard.updateBanner.deploy', { version: latestTag })}
          </button>
        ) : null}

        {showUpdateAvailable && !canDeploy ? (
          <Link
            to={settingsGroupPath('systemUpdate')}
            className="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-amber-300 bg-amber-50 text-amber-950 text-sm font-semibold hover:bg-amber-100"
          >
            <Settings className="w-4 h-4" />
            {t('dashboard.updateBanner.configureDeploy')}
          </Link>
        ) : null}

        <Link
          to="/update"
          className="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-indigo-800 dark:text-indigo-200 text-sm font-semibold hover:underline"
        >
          {t('dashboard.updateBanner.details')}
        </Link>
      </div>
    </div>
  );
};

export default SystemUpdateBanner;
