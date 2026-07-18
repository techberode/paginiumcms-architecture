// frontend/src/hooks/useAdminViewMode.ts
import { useCallback, useEffect, useState } from 'react';

export type AdminViewMode = 'list' | 'list-preview' | 'preview';

export type AdminViewSection = 'media' | 'articles' | 'pages';

const STORAGE_PREFIX = 'paginium.admin.viewMode';

function storageKey(section: AdminViewSection): string {
  return `${STORAGE_PREFIX}.${section}`;
}

function readStoredMode(section: AdminViewSection, fallback: AdminViewMode): AdminViewMode {
  try {
    if (typeof window === 'undefined' || !window.localStorage) {
      return fallback;
    }

    const raw = window.localStorage.getItem(storageKey(section));
    if (raw === 'list' || raw === 'list-preview' || raw === 'preview') {
      return raw;
    }
  } catch {
    return fallback;
  }

  return fallback;
}

export function useAdminViewMode(section: AdminViewSection, defaultMode: AdminViewMode = 'preview') {
  const [mode, setModeState] = useState<AdminViewMode>(() => readStoredMode(section, defaultMode));

  useEffect(() => {
    setModeState(readStoredMode(section, defaultMode));
  }, [section, defaultMode]);

  const setMode = useCallback(
    (next: AdminViewMode) => {
      setModeState(next);
      try {
        if (typeof window !== 'undefined' && window.localStorage) {
          window.localStorage.setItem(storageKey(section), next);
        }
      } catch {
        // Ignore storage errors in restricted environments.
      }
    },
    [section]
  );

  return { mode, setMode };
}
