import {
  useQuery,
  type QueryKey,
  type UseQueryOptions,
  type UseQueryResult,
} from '@tanstack/react-query';

/** P2 admin panels — stale-while-revalidate, longer TTL than list views. */
export const ADMIN_SECONDARY_STALE_MS = 120_000;
export const ADMIN_SECONDARY_GC_MS = 10 * 60_000;

type AdminSecondaryQueryOptions<TData> = Omit<
  UseQueryOptions<TData, Error, TData, QueryKey>,
  'staleTime' | 'gcTime' | 'refetchOnWindowFocus' | 'placeholderData'
>;

export function useAdminSecondaryQuery<TData>(
  options: AdminSecondaryQueryOptions<TData>
): UseQueryResult<TData, Error> {
  return useQuery({
    staleTime: ADMIN_SECONDARY_STALE_MS,
    gcTime: ADMIN_SECONDARY_GC_MS,
    refetchOnWindowFocus: false,
    placeholderData: (previous) => previous,
    ...options,
  });
}
