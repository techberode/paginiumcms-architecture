// frontend/src/context/SettingsContext.tsx
// === SettingsContext (Iterácia 4) ===
// Globálny prístup k efektívnym nastaveniam CMS. Načíta verejný výrez z
// GET /api/settings/public; admin môže volať reload() po uložení v SettingsView.
import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { getPublicSettings, PublicSettings } from '../api/settings';
import { useAuth } from '../hooks/useAuth';

/** Predvolené hodnoty – fallback ak API zlyhá alebo používateľ nie je prihlásený. */
const DEFAULT_PUBLIC: PublicSettings = {
  general: { siteName: 'PaginiumCMS', language: 'sk', maintenanceMode: false },
  content: {
    itemsPerPage: 20,
    defaultStatus: 'draft',
    autoSaveInterval: 60,
    lockTtl: 300,
  },
  editor: {
    defaultEditor: 'markdown',
    spellcheck: true,
    tabSize: 2,
  },
};

interface SettingsContextType {
  settings: PublicSettings;
  loading: boolean;
  /** Bodková notácia: get('content.autoSaveInterval') */
  get: (key: string, fallback?: unknown) => unknown;
  reload: () => Promise<void>;
}

const SettingsContext = createContext<SettingsContextType | undefined>(undefined);

export const SettingsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user } = useAuth();
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC);
  const [loading, setLoading] = useState(true);

  const reload = useCallback(async () => {
    if (!user) {
      setSettings(DEFAULT_PUBLIC);
      setLoading(false);
      return;
    }

    setLoading(true);
    try {
      const payload = await getPublicSettings();
      if (payload) {
        setSettings(payload);
      }
    } finally {
      setLoading(false);
    }
  }, [user]);

  useEffect(() => {
    void reload();
  }, [reload]);

  const get = useCallback(
    (key: string, fallback?: unknown): unknown => {
      const parts = key.split('.');
      let current: unknown = settings;
      for (const part of parts) {
        if (current === null || typeof current !== 'object') {
          return fallback;
        }
        current = (current as Record<string, unknown>)[part];
      }
      return current ?? fallback;
    },
    [settings]
  );

  const value = useMemo(
    () => ({ settings, loading, get, reload }),
    [settings, loading, get, reload]
  );

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>;
};

export function useSettingsContext(): SettingsContextType {
  const ctx = useContext(SettingsContext);
  if (!ctx) {
    throw new Error('useSettingsContext musí byť v SettingsProvider');
  }
  return ctx;
}

export default SettingsContext;
