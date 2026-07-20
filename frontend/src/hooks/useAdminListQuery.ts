import {
  useQuery,
  type QueryKey,
  type UseQueryOptions,
  type UseQueryResult,
} from '@tanstack/react-query';

type AdminListQueryOptions<TData> = Omit<
  UseQueryOptions<TData, Error, TData, QueryKey>,
  'staleTime' | 'gcTime' | 'refetchOnWindowFocus' | 'placeholderData'
>;

/** Stale-while-revalidate defaults for admin list/detail views (It.53). */
export function useAdminListQuery<TData>(
  options: AdminListQueryOptions<TData>
): UseQueryResult<TData, Error> {
  return useQuery({
    staleTime: 30_000,
    gcTime: 5 * 60_000,
    refetchOnWindowFocus: false,
    placeholderData: (previous) => previous,
    ...options,
  });
}
