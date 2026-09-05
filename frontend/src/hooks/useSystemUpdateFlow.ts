import { useCallback, useEffect, useState } from 'react';
import {
  checkSystemUpdate,
  getSystemUpdateStatus,
  runSystemUpdate,
  type SystemUpdateCheckResult,
  type SystemUpdateDeployReadiness,
  type SystemUpdateStatus,
} from '../api/systemUpdate';

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
  refreshCheck: () => Promise<SystemUpdateCheckResult | null>;
  deployLatest: (tag: string) => Promise<{ ok: boolean; error?: string }>;
}

export function useSystemUpdateFlow(enabled: boolean): SystemUpdateFlowState {
  const [status, setStatus] = useState<SystemUpdateStatus | null>(null);
  const [check, setCheck] = useState<SystemUpdateCheckResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [checking, setChecking] = useState(false);
  const [deploying, setDeploying] = useState(false);

  const readiness = check?.deploy_readiness ?? status?.deploy_readiness ?? null;

  const latestTag =
    check?.update?.latest_tag ??
    check?.remote.latest_release_tag ??
    null;

  const updateStatus = check?.update?.status ?? null;

  const canDeploy =
    Boolean(latestTag) &&
    updateStatus === 'update_available' &&
    readiness?.ready === true;

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
      return null;
    }
    setChecking(true);
    try {
      const next = await checkSystemUpdate();
      setCheck(next);
      if (next?.deploy_readiness) {
        setStatus((prev) =>
          prev ? { ...prev, deploy_readiness: next.deploy_readiness } : prev
        );
      }
      return next;
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
        await refreshStatus();
        await refreshCheck();
        return { ok: true };
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
