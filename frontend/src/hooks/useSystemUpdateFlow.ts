import { useCallback, useEffect, useState } from 'react';
import {
  checkSystemUpdate,
  getSystemUpdateStatus,
  runSystemUpdate,
  type SystemUpdateCheckResult,
  type SystemUpdateDeployReadiness,
  type SystemUpdateStatus,
} from '../api/systemUpdate';
import { interpretDeployRunResult } from '../utils/deployRunResult';

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
  refreshStatus: () => Promise<void>;
  refreshCheck: () => Promise<{ data: SystemUpdateCheckResult | null; error?: string }>;
  deployLatest: (tag: string) => Promise<{ ok: boolean; skipped?: boolean; error?: string }>;
}

export function useSystemUpdateFlow(enabled: boolean): SystemUpdateFlowState {
  const [status, setStatus] = useState<SystemUpdateStatus | null>(null);
  const [check, setCheck] = useState<SystemUpdateCheckResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [checking, setChecking] = useState(false);
  const [deploying, setDeploying] = useState(false);

  const readiness = check?.deploy_readiness ?? status?.deploy_readiness ?? null;

  const latestTag =
    check?.remote.latest_release_tag ??
    check?.update?.latest_tag ??
    null;

  const updateStatus = check?.update?.status ?? null;

  const canDeploy =
    Boolean(latestTag) &&
    updateStatus === 'update_available' &&
    readiness?.ready === true &&
    status?.config?.deployEnabled === true &&
    status?.job_registered === true;

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

  const refreshCheck = useCallback(async () => {
    if (!enabled) {
      return { data: null };
    }
    setChecking(true);
    try {
      const result = await checkSystemUpdate();
      setCheck(result.data);
      const readinessUpdate = result.data?.deploy_readiness;
      if (readinessUpdate) {
        setStatus((prev) =>
          prev ? { ...prev, deploy_readiness: readinessUpdate } : prev
        );
      }
      return result;
    } finally {
      setChecking(false);
    }
  }, [enabled]);

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
        await refreshStatus();
        await refreshCheck();
        return { ok: true, skipped: outcome.skipped };
      } finally {
        setDeploying(false);
      }
    },
    [enabled, refreshCheck, refreshStatus]
  );

  useEffect(() => {
    if (!enabled) {
      return;
    }
    void refreshStatus();
    void refreshCheck();
  }, [enabled, refreshCheck, refreshStatus]);

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
    refreshStatus,
    refreshCheck,
    deployLatest,
  };
}
