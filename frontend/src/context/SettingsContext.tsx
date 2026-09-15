// frontend/src/context/SettingsContext.tsx
// === SettingsContext (Iterácia 4) ===
// Globálny prístup k efektívnym nastaveniam CMS. Načíta verejný výrez z
// GET /api/settings/public; admin môže volať reload() po uložení v SettingsView.
import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { getPublicSettings, PublicSettings } from '../api/settings';
import { useAuth } from '../hooks/useAuth';
import { debugLogProvider } from '../utils/debugLog';
import {
  mergePublicSettings,
  mergeSettingsPatch,
  omitPreviewGroup,
} from '../utils/settingsPreview';

/** Predvolené hodnoty – fallback ak API zlyhá alebo používateľ nie je prihlásený. */
const DEFAULT_PUBLIC: PublicSettings = {
  general: { siteName: 'PaginiumCMS', language: 'sk' },
  branding: { logoUrl: '', faviconUrl: '' },
  maintenance: { mode: 'off' },
  content: {
    itemsPerPage: 20,
    blogItemsPerPage: 6,
    showReadingTime: true,
    articlePrintEnabled: false,
    shareEnabled: true,
    shareOnArticles: true,
    shareOnPages: false,
    shareFacebook: true,
    shareX: true,
    shareLinkedin: true,
    shareEmail: true,
    shareCopy: true,
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
    sidebarColor: 'default',
    topbarColor: 'default',
    chromeGradient: false,
    chromeGradientDirection: 'to-bottom',
    navPlacement: 'side',
  },
  navigationUi: {
    defaultPreviewScale: 1.5,
    maxTooltipWidthPx: 280,
    enableHoverAnimations: true,
  },
  navigation: {
    placement: 'top',
    sideBreakpoint: 'lg',
    expandAnimation: true,
    maxDepth: 3,
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
  newsletter: {
    footerEnabled: false,
    footerHint: '',
    enabledPreferences: ['weekly_digest', 'general_news'],
    requireConsentCheckbox: false,
    requireDoubleOptIn: false,
  },
  privacy: {
    cookieBannerEnabled: false,
    cookieBannerText: '',
    cookiePolicyUrl: '',
    cookieShowRejectButton: true,
    cookiePolicyPageTitle: '',
    cookiePolicyIntro: '',
    cookiePolicySectionsJson: '[]',
    privacyContactName: '',
    privacyContactEmail: '',
    privacyContactPhone: '',
    privacyContactAddress: '',
    cookiePolicyShowCategoriesTable: true,
    cookiePolicyShowStorageInventory: true,
    cookiePolicyShowDefaultRights: true,
    cookiePolicyShowManagePanel: true,
  },
  appearance: {
    colorScheme: 'indigo-classic',
    mode: 'system',
    allowUserToggle: true,
    previewTemplate: 'hero-content',
    activeThemeId: 'paginium-core',
    themeScriptsEnabled: false,
    themeScripts: [],
  },
  layout: {
    builderMode: 'templates',
    defaultTemplate: 'hero-content',
    developerRequiresAdmin: true,
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
  projectPlanner: {
    enabled: true,
  },
  social: {
    enabled: true,
    links: [
      {
        platform: 'github',
        url: 'https://github.com/techberode/paginiumcms-architecture',
        label: 'GitHub',
      },
    ],
  },
};

export interface SettingsContextType {
  settings: PublicSettings;
  loading: boolean;
  /** Bodková notácia: get('content.autoSaveInterval') */
  get: (key: string, fallback?: unknown) => unknown;
  reload: () => Promise<void>;
  /** Overlay public settings in-memory (Apply). Discarded on clearPreview / leaving Settings. */
  applyPreview: (patch: Partial<PublicSettings>) => void;
  clearPreview: () => void;
  clearPreviewGroup: (group: string) => void;
  hasUnsavedPreview: boolean;
}

export const SETTINGS_PREVIEW_NOOP: Pick<
  SettingsContextType,
  'applyPreview' | 'clearPreview' | 'clearPreviewGroup' | 'hasUnsavedPreview'
> = {
  applyPreview: () => undefined,
  clearPreview: () => undefined,
  clearPreviewGroup: () => undefined,
  hasUnsavedPreview: false,
};

export const SettingsContext = createContext<SettingsContextType | undefined>(undefined);

export const SettingsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user } = useAuth();
  const [persisted, setPersisted] = useState<PublicSettings>(DEFAULT_PUBLIC);
  const [previewPatch, setPreviewPatch] = useState<Partial<PublicSettings> | null>(null);
  const [loading, setLoading] = useState(true);
  const hasLoadedRef = useRef(false);

  const settings = useMemo(
    () => (previewPatch ? mergePublicSettings(persisted, previewPatch) : persisted),
    [persisted, previewPatch]
  );
  const hasUnsavedPreview = Boolean(previewPatch && Object.keys(previewPatch).length > 0);

  const applyPreview = useCallback((patch: Partial<PublicSettings>) => {
    setPreviewPatch((prev) =>
      mergeSettingsPatch((prev ?? {}) as Record<string, unknown>, patch as Record<string, unknown>) as Partial<PublicSettings>
    );
  }, []);

  const clearPreview = useCallback(() => {
    setPreviewPatch(null);
  }, []);

  const clearPreviewGroup = useCallback((group: string) => {
    setPreviewPatch((prev) => (prev ? omitPreviewGroup(prev, group) : null));
  }, []);

  const reload = useCallback(async () => {
    if (!hasLoadedRef.current) {
      setLoading(true);
    }
    debugLogProvider('settings', 'reload.start', { hasUser: Boolean(user) });
    try {
      const payload = await getPublicSettings();
      if (payload) {
        setPersisted(payload);
        debugLogProvider('settings', 'reload.done', {
          source: 'api',
          siteName: payload.general?.siteName,
        });
      } else if (!user) {
        setPersisted(DEFAULT_PUBLIC);
        debugLogProvider('settings', 'reload.fallback', { source: 'default_public' });
      }
    } catch (error) {
      if (!user) {
        setPersisted(DEFAULT_PUBLIC);
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
    () => ({
      settings,
      loading,
      get,
      reload,
      applyPreview,
      clearPreview,
      clearPreviewGroup,
      hasUnsavedPreview,
    }),
    [settings, loading, get, reload, applyPreview, clearPreview, clearPreviewGroup, hasUnsavedPreview]
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

export const TestSettingsProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const value = useMemo<SettingsContextType>(
    () => ({
      settings: DEFAULT_PUBLIC,
      loading: false,
      get: (key: string, fallback?: unknown) => {
        const parts = key.split('.');
        let current: unknown = DEFAULT_PUBLIC;
        for (const part of parts) {
          if (current === null || typeof current !== 'object') {
            return fallback;
          }
          current = (current as Record<string, unknown>)[part];
        }
        return current ?? fallback;
      },
      reload: async () => undefined,
      applyPreview: SETTINGS_PREVIEW_NOOP.applyPreview,
      clearPreview: SETTINGS_PREVIEW_NOOP.clearPreview,
      clearPreviewGroup: SETTINGS_PREVIEW_NOOP.clearPreviewGroup,
      hasUnsavedPreview: false,
    }),
    []
  );

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>;
};

export default SettingsContext;
