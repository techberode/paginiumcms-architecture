import { useCallback, useEffect, useRef, useState } from 'react';
import {
  checkSystemUpdate,
  getSystemUpdateStatus,
  runSystemUpdate,
  type SystemUpdateCheckResult,
  type SystemUpdateDeployReadiness,
  type SystemUpdateStatus,
} from '../api/systemUpdate';
import { interpretDeployRunResult } from '../utils/deployRunResult';
import {
  hasSystemUpdateSessionAutoCheck,
  markSystemUpdateSessionAutoCheck,
  readSystemUpdateCheckCache,
  writeSystemUpdateCheckCache,
} from '../utils/systemUpdateCheckCache';

export interface SystemUpdateFlowState {
  status: SystemUpdateStatus | null;
  check: SystemUpdateCheckResult | null;
  readiness: SystemUpdateDeployReadiness | null;
  loading: boolean;
  checking: boolean;
  deploying: boolean;
  latestTag: string | null;
  updateStatus: 'current' | 'update_available' | 'unknown' | null;
  canDeploy: boolean;
  lastCheckedAt: number | null;
  currentVersion: string | null;
  refreshStatus: () => Promise<void>;
  refreshCheck: (options?: { force?: boolean }) => Promise<{ data: SystemUpdateCheckResult | null; error?: string }>;
  deployLatest: (tag: string) => Promise<{ ok: boolean; skipped?: boolean; error?: string }>;
}

export function useSystemUpdateFlow(enabled: boolean): SystemUpdateFlowState {
  const [status, setStatus] = useState<SystemUpdateStatus | null>(null);
  const [check, setCheck] = useState<SystemUpdateCheckResult | null>(null);
  const [lastCheckedAt, setLastCheckedAt] = useState<number | null>(null);
  const [loading, setLoading] = useState(false);
  const [checking, setChecking] = useState(false);
  const [deploying, setDeploying] = useState(false);
  const autoRanRef = useRef(false);

  const readiness = check?.deploy_readiness ?? status?.deploy_readiness ?? null;

  const latestTag =
    check?.remote.latest_release_tag ??
    check?.update?.latest_tag ??
    null;

  const updateStatus = check?.update?.status ?? null;

  const currentVersion =
    check?.update?.current_version?.trim() ||
    check?.update?.current_tag?.trim() ||
    status?.app_version?.trim() ||
    null;

  const canDeploy =
    Boolean(latestTag) &&
    updateStatus === 'update_available' &&
    readiness?.ready === true &&
    status?.config?.deployEnabled === true &&
    status?.job_registered === true;

  const persistSnapshot = useCallback(
    (nextCheck: SystemUpdateCheckResult, nextStatus: SystemUpdateStatus | null) => {
      const checkedAt = Date.now();
      setLastCheckedAt(checkedAt);
      writeSystemUpdateCheckCache({
        checkedAt,
        check: nextCheck,
        status: nextStatus,
      });
    },
    []
  );

  const refreshStatus = useCallback(async () => {
    if (!enabled) {
      return;
    }
    setLoading(true);
    try {
      const next = await getSystemUpdateStatus();
      setStatus(next);
    } finally {
      setLoading(false);
    }
  }, [enabled]);

  const refreshCheck = useCallback(
    async (callOptions?: { force?: boolean }) => {
      if (!enabled) {
        return { data: null };
      }
      if (callOptions?.force !== true) {
        return { data: check };
      }

      setChecking(true);
      try {
        const [checkResult, statusResult] = await Promise.all([
          checkSystemUpdate(),
          getSystemUpdateStatus(),
        ]);
        const nextCheck = checkResult.data;
        if (nextCheck) {
          setCheck(nextCheck);
          const readinessUpdate = nextCheck.deploy_readiness;
          if (statusResult) {
            setStatus(
              readinessUpdate
                ? { ...statusResult, deploy_readiness: readinessUpdate }
                : statusResult
            );
          }
          persistSnapshot(nextCheck, statusResult);
        } else if (statusResult) {
          setStatus(statusResult);
        }
        return checkResult;
      } finally {
        setChecking(false);
      }
    },
    [check, enabled, persistSnapshot]
  );

  const deployLatest = useCallback(
    async (tag: string) => {
      if (!enabled || tag.trim() === '') {
        return { ok: false, error: 'missing_ref' };
      }
      setDeploying(true);
      try {
        const { data, error } = await runSystemUpdate(tag.trim());
        if (!data) {
          return { ok: false, error: error ?? 'deploy_failed' };
        }
        const outcome = interpretDeployRunResult(data);
        if (!outcome.ok) {
          return { ok: false, error: outcome.error ?? error ?? 'deploy_failed' };
        }
        await refreshCheck({ force: true });
        return { ok: true, skipped: outcome.skipped };
      } finally {
        setDeploying(false);
      }
    },
    [enabled, refreshCheck]
  );

  useEffect(() => {
    if (!enabled) {
      return;
    }
    if (autoRanRef.current) {
      return;
    }
    autoRanRef.current = true;

    const cached = readSystemUpdateCheckCache();
    if (cached) {
      setCheck(cached.check);
      if (cached.status) {
        setStatus(cached.status);
      }
      setLastCheckedAt(cached.checkedAt);
    }

    if (!hasSystemUpdateSessionAutoCheck()) {
      markSystemUpdateSessionAutoCheck();
      void refreshCheck({ force: true });
    }
  }, [enabled, refreshCheck]);

  return {
    status,
    check,
    readiness,
    loading,
    checking,
    deploying,
    latestTag,
    updateStatus,
    canDeploy,
    lastCheckedAt,
    currentVersion,
    refreshStatus,
    refreshCheck,
    deployLatest,
  };
}
