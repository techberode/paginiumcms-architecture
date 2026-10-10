import {
  useQuery,
  type QueryKey,
  type UseQueryOptions,
  type UseQueryResult,
} from '@tanstack/react-query';

/** P2 admin panels — stale-while-revalidate, longer TTL than list views. */
export const ADMIN_SECONDARY_STALE_MS = 120_000;
/** When It.100 load hint is busy, stretch P2 cache on the client. */
export const ADMIN_SECONDARY_STALE_BUSY_MS = 300_000;
export const ADMIN_SECONDARY_GC_MS = 10 * 60_000;

type AdminSecondaryQueryOptions<TData> = Omit<
  UseQueryOptions<TData, Error, TData, QueryKey>,
  'staleTime' | 'gcTime' | 'refetchOnWindowFocus' | 'placeholderData'
> & {
  adminLoadBusy?: boolean;
};

export function useAdminSecondaryQuery<TData>(
  options: AdminSecondaryQueryOptions<TData>
): UseQueryResult<TData, Error> {
  const { adminLoadBusy = false, ...queryOptions } = options;

  return useQuery({
    staleTime: adminLoadBusy ? ADMIN_SECONDARY_STALE_BUSY_MS : ADMIN_SECONDARY_STALE_MS,
    gcTime: ADMIN_SECONDARY_GC_MS,
    refetchOnWindowFocus: false,
    placeholderData: (previous) => previous,
    ...queryOptions,
  });
}
