import { useCallback, useEffect, useState } from 'react';
import { getSetupStatus, type SetupStatus } from '../api/setup';

export function useSetupStatus(): {
  status: SetupStatus | null;
  loading: boolean;
  needsSetup: boolean;
  refresh: () => Promise<void>;
} {
  const [status, setStatus] = useState<SetupStatus | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const next = await getSetupStatus();
      setStatus(next);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  return {
    status,
    loading,
    needsSetup: status?.needsSetup === true,
    refresh,
  };
}
