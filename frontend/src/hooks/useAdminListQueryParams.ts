import { useCallback, useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import type { SortDirection } from './useColumnSort';

export interface AdminListQueryState {
  page: number;
  search: string;
  debouncedSearch: string;
  statusFilter: string;
  seoIssuesOnly: boolean;
  sortField: string;
  sortDirection: SortDirection;
}

export interface AdminListQueryActions {
  setSearch: (value: string) => void;
  setPage: (page: number) => void;
  setStatusFilter: (value: string) => void;
  setSeoIssuesOnly: (value: boolean) => void;
  handleSort: (field: string) => void;
  resetFilters: () => void;
}

function parsePositiveInt(value: string | null, fallback: number): number {
  const parsed = Number.parseInt(value ?? '', 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
}

export function parseSortParam(
  value: string | null,
  defaultField: string,
  defaultDirection: SortDirection = 'desc'
): { sortField: string; sortDirection: SortDirection } {
  if (!value) {
    return { sortField: defaultField, sortDirection: defaultDirection };
  }
  if (value.startsWith('-')) {
    return { sortField: value.slice(1) || defaultField, sortDirection: 'desc' };
  }
  return { sortField: value, sortDirection: 'asc' };
}

export function formatSortParam(sortField: string, sortDirection: SortDirection): string {
  return sortDirection === 'desc' ? `-${sortField}` : sortField;
}

export function useAdminListQueryParams(
  defaultSortField: string,
  defaultSortDirection: SortDirection = 'desc'
): AdminListQueryState & AdminListQueryActions {
  const [searchParams, setSearchParams] = useSearchParams();
  const [search, setSearchState] = useState(() => searchParams.get('q') ?? '');
  const [debouncedSearch, setDebouncedSearch] = useState(() => (searchParams.get('q') ?? '').trim());

  const page = parsePositiveInt(searchParams.get('page'), 1);
  const statusFilter = searchParams.get('status') ?? 'all';
  const seoIssuesOnly = searchParams.get('seo') === '1';
  const { sortField, sortDirection } = useMemo(
    () => parseSortParam(searchParams.get('sort'), defaultSortField, defaultSortDirection),
    [searchParams, defaultSortField, defaultSortDirection]
  );

  const patchParams = useCallback(
    (patch: {
      page?: number;
      q?: string;
      status?: string;
      seo?: boolean;
      sortField?: string;
      sortDirection?: SortDirection;
      resetPage?: boolean;
    }) => {
      const next = new URLSearchParams(searchParams);

      if (patch.q !== undefined) {
        const trimmed = patch.q.trim();
        if (trimmed.length >= 2) {
          next.set('q', trimmed);
        } else {
          next.delete('q');
        }
      }

      if (patch.status !== undefined) {
        if (patch.status === 'all' || patch.status === '') {
          next.delete('status');
        } else {
          next.set('status', patch.status);
        }
      }

      if (patch.seo !== undefined) {
        if (patch.seo) {
          next.set('seo', '1');
        } else {
          next.delete('seo');
        }
      }

      if (patch.sortField !== undefined && patch.sortDirection !== undefined) {
        const sortValue = formatSortParam(patch.sortField, patch.sortDirection);
        if (sortValue === formatSortParam(defaultSortField, defaultSortDirection)) {
          next.delete('sort');
        } else {
          next.set('sort', sortValue);
        }
      }

      if (patch.page !== undefined) {
        if (patch.page <= 1) {
          next.delete('page');
        } else {
          next.set('page', String(patch.page));
        }
      } else if (patch.resetPage) {
        next.delete('page');
      }

      setSearchParams(next, { replace: true });
    },
    [defaultSortDirection, defaultSortField, searchParams, setSearchParams]
  );

  useEffect(() => {
    const timer = window.setTimeout(() => {
      setDebouncedSearch(search.trim());
    }, 300);
    return () => window.clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    const trimmed = debouncedSearch;
    const currentQ = searchParams.get('q') ?? '';
    const normalizedQ = trimmed.length >= 2 ? trimmed : '';
    if (normalizedQ === currentQ) {
      return;
    }
    patchParams({ q: trimmed, resetPage: true });
  }, [debouncedSearch, patchParams, searchParams]);

  const setSearch = useCallback((value: string) => {
    setSearchState(value);
  }, []);

  const setPage = useCallback(
    (nextPage: number) => {
      patchParams({ page: Math.max(1, nextPage) });
    },
    [patchParams]
  );

  const setStatusFilter = useCallback(
    (value: string) => {
      patchParams({ status: value, resetPage: true });
    },
    [patchParams]
  );

  const setSeoIssuesOnly = useCallback(
    (value: boolean) => {
      patchParams({ seo: value, resetPage: true });
    },
    [patchParams]
  );

  const handleSort = useCallback(
    (field: string) => {
      if (sortField === field) {
        patchParams({
          sortField: field,
          sortDirection: sortDirection === 'asc' ? 'desc' : 'asc',
          resetPage: true,
        });
        return;
      }
      patchParams({ sortField: field, sortDirection: 'asc', resetPage: true });
    },
    [patchParams, sortDirection, sortField]
  );

  const resetFilters = useCallback(() => {
    setSearchState('');
    setDebouncedSearch('');
    setSearchParams({}, { replace: true });
  }, [setSearchParams]);

  return {
    page,
    search,
    debouncedSearch,
    statusFilter,
    seoIssuesOnly,
    sortField,
    sortDirection,
    setSearch,
    setPage,
    setStatusFilter,
    setSeoIssuesOnly,
    handleSort,
    resetFilters,
  };
}
