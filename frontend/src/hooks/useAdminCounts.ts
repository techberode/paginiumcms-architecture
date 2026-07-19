// frontend/src/hooks/useAdminCounts.ts
import { useCallback, useEffect, useState } from 'react';
import { AdminCounts, getAdminCounts } from '../api/counts';
import { useAuth } from './useAuth';
import { useSettingsContext } from '../context/SettingsContext';

export function useAdminCounts() {
  const { user } = useAuth();
  const { get } = useSettingsContext();
  const showListCounts = Boolean(get('ui.showListCounts') ?? true);
  const [counts, setCounts] = useState<AdminCounts | null>(null);

  const refresh = useCallback(async () => {
    if (!user || !showListCounts) {
      setCounts(null);
      return;
    }
    const data = await getAdminCounts();
    setCounts(data);
  }, [user, showListCounts]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  return { counts, showListCounts, refresh };
}

export default useAdminCounts;
