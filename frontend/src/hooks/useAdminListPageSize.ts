import { useCallback, useMemo, useState } from 'react';
import { useSettingsContext } from '../context/SettingsContext';

export const ADMIN_PAGE_SIZE_OPTIONS = [5, 10, 20, 50, 100] as const;

const STORAGE_PREFIX = 'paginium-admin-page-size-';

function clampPageSize(value: number): number {
  return Math.max(5, Math.min(100, value));
}

function readStoredPageSize(moduleKey: string): number | null {
  if (typeof window === 'undefined') {
    return null;
  }

  const raw = window.localStorage.getItem(`${STORAGE_PREFIX}${moduleKey}`);
  if (raw === null) {
    return null;
  }

  const parsed = Number(raw);
  return Number.isFinite(parsed) ? clampPageSize(parsed) : null;
}

export function useAdminListPageSize(moduleKey: string): [number, (value: number) => void] {
  const { settings } = useSettingsContext();
  const defaultSize = clampPageSize(
    Number(settings.ui?.adminListPageSize ?? settings.content?.itemsPerPage ?? 20)
  );

  const initial = useMemo(
    () => readStoredPageSize(moduleKey) ?? defaultSize,
    [defaultSize, moduleKey]
  );

  const [pageSize, setPageSizeState] = useState(initial);

  const setPageSize = useCallback(
    (value: number) => {
      const next = clampPageSize(value);
      setPageSizeState(next);
      if (typeof window !== 'undefined') {
        window.localStorage.setItem(`${STORAGE_PREFIX}${moduleKey}`, String(next));
      }
    },
    [moduleKey]
  );

  return [pageSize, setPageSize];
}
