import { useQuery } from '@tanstack/react-query';
import { getAdminLoadHint, type AdminLoadHint } from '../api/metrics';
import { queryKeys } from '../api/queryKeys';

const LOAD_HINT_STALE_MS = 60_000;

export function useAdminLoadHint(enabled = true): AdminLoadHint {
  const { data } = useQuery({
    queryKey: queryKeys.metrics.loadHint,
    queryFn: getAdminLoadHint,
    staleTime: LOAD_HINT_STALE_MS,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    enabled,
  });

  return data ?? { level: 'normal', reasons: [] };
}

export function isAdminLoadBusy(hint: AdminLoadHint): boolean {
  return hint.level === 'busy';
}
