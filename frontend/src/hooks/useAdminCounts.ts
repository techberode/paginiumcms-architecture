import { useQuery } from '@tanstack/react-query';
import { getAdminCounts, type AdminCounts } from '../api/counts';
import { queryKeys } from '../api/queryKeys';
import { useAuth } from './useAuth';
import { useSettingsContext } from '../context/SettingsContext';

export function useAdminCounts() {
  const { user } = useAuth();
  const { get } = useSettingsContext();
  const showListCounts = Boolean(get('ui.showListCounts') ?? true);

  const query = useQuery<AdminCounts | null>({
    queryKey: queryKeys.adminCounts(user?.id),
    enabled: Boolean(user) && showListCounts,
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    queryFn: async () => getAdminCounts(),
  });

  return {
    counts: query.data ?? null,
    showListCounts,
    refresh: () => void query.refetch(),
  };
}

export default useAdminCounts;
