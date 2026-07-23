// frontend/src/context/SettingsContext.tsx
// === SettingsContext (Iterácia 4) ===
// Globálny prístup k efektívnym nastaveniam CMS. Načíta verejný výrez z
// GET /api/settings/public; admin môže volať reload() po uložení v SettingsView.
import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { getPublicSettings, PublicSettings } from '../api/settings';
import { useAuth } from '../hooks/useAuth';
import { debugLogProvider } from '../utils/debugLog';

/** Predvolené hodnoty – fallback ak API zlyhá alebo používateľ nie je prihlásený. */
const DEFAULT_PUBLIC: PublicSettings = {
  general: { siteName: 'PaginiumCMS', language: 'sk' },
  maintenance: { mode: 'off' },
  content: {
    itemsPerPage: 20,
    blogItemsPerPage: 6,
    showReadingTime: true,
    defaultStatus: 'draft',
    autoSaveInterval: 60,
    lockTtl: 300,
  },
  editor: {
    defaultEditor: 'markdown',
    spellcheck: true,
    tabSize: 2,
  },
  notifications: {
    toastEnabled: true,
    toastPosition: 'top-right',
    toastDuration: 3000,
    toastDebugMode: false,
  },
  ui: {
    showListCounts: true,
    adminListPageSize: 20,
    openLinksInNewTab: false,
  },
  comments: {
    enabled: true,
    requireApproval: true,
    allowGuestComments: true,
  },
  contact: {
    subjects: 'Všeobecný dotaz\nTechnická podpora\nObchodná spolupráca\nInformácie o produkte',
    allowCustomSubject: true,
  },
  company: {
    showOnContactPage: true,
    name: '',
    legalName: '',
    ico: '',
    dic: '',
    icDph: '',
    address: '',
    email: '',
    phone: '',
    website: '',
    mapEmbedUrl: '',
  },
  demo: {
    enabled: false,
  },
};

interface SettingsContextType {
  settings: PublicSettings;
  loading: boolean;
  /** Bodková notácia: get('content.autoSaveInterval') */
  get: (key: string, fallback?: unknown) => unknown;
  reload: () => Promise<void>;
}

export const SettingsContext = createContext<SettingsContextType | undefined>(undefined);

export const SettingsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user } = useAuth();
  const [settings, setSettings] = useState<PublicSettings>(DEFAULT_PUBLIC);
  const [loading, setLoading] = useState(true);
  const hasLoadedRef = useRef(false);

  const reload = useCallback(async () => {
    if (!hasLoadedRef.current) {
      setLoading(true);
    }
    debugLogProvider('settings', 'reload.start', { hasUser: Boolean(user) });
    try {
      const payload = await getPublicSettings();
      if (payload) {
        setSettings(payload);
        debugLogProvider('settings', 'reload.done', {
          source: 'api',
          siteName: payload.general?.siteName,
        });
      } else if (!user) {
        setSettings(DEFAULT_PUBLIC);
        debugLogProvider('settings', 'reload.fallback', { source: 'default_public' });
      }
    } catch (error) {
      if (!user) {
        setSettings(DEFAULT_PUBLIC);
      }
      debugLogProvider('settings', 'reload.error', {
        message: error instanceof Error ? error.message : 'unknown',
        usedFallback: !user,
      });
    } finally {
      hasLoadedRef.current = true;
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
