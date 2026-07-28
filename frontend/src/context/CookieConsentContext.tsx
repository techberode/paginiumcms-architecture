import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { useSettingsContext } from './SettingsContext';
import {
  clearFunctionalStorageIfDenied,
  readStoredCookieConsent,
  writeStoredCookieConsent,
  type CookieConsentPreferences,
  type StoredCookieConsent,
} from '../utils/cookieConsent';

interface CookieConsentContextValue {
  bannerEnabled: boolean;
  bannerText: string;
  policyUrl: string;
  showRejectButton: boolean;
  decided: boolean;
  functional: boolean;
  analytics: boolean;
  showBanner: boolean;
  showSettings: boolean;
  openSettings: () => void;
  closeSettings: () => void;
  acceptAll: () => void;
  rejectOptional: () => void;
  savePreferences: (preferences: Pick<CookieConsentPreferences, 'functional' | 'analytics'>) => void;
}

const CookieConsentContext = createContext<CookieConsentContextValue | null>(null);

export const CookieConsentProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { settings } = useSettingsContext();
  const privacy = settings.privacy;
  const bannerEnabled = privacy?.cookieBannerEnabled === true;
  const bannerText = (privacy?.cookieBannerText ?? '').trim();
  const policyUrl = (privacy?.cookiePolicyUrl ?? '').trim();
  const showRejectButton = privacy?.cookieShowRejectButton !== false;

  const [stored, setStored] = useState<StoredCookieConsent | null>(() => readStoredCookieConsent());
  const [showSettings, setShowSettings] = useState(false);

  const decided = !bannerEnabled || stored !== null;
  const functional = !bannerEnabled || (stored?.functional ?? false);
  const analytics = !bannerEnabled || (stored?.analytics ?? false);
  const showBanner = bannerEnabled && stored === null;

  const persist = useCallback((preferences: CookieConsentPreferences) => {
    const next = writeStoredCookieConsent(preferences);
    setStored(next);
    clearFunctionalStorageIfDenied();
    setShowSettings(false);
  }, []);

  const acceptAll = useCallback(() => {
    persist({ necessary: true, functional: true, analytics: true });
  }, [persist]);

  const rejectOptional = useCallback(() => {
    persist({ necessary: true, functional: false, analytics: false });
  }, [persist]);

  const savePreferences = useCallback(
    (preferences: Pick<CookieConsentPreferences, 'functional' | 'analytics'>) => {
      persist({ necessary: true, functional: preferences.functional, analytics: preferences.analytics });
    },
    [persist]
  );

  useEffect(() => {
    if (!bannerEnabled) {
      return;
    }
    clearFunctionalStorageIfDenied();
  }, [bannerEnabled, stored?.functional]);

  const value = useMemo<CookieConsentContextValue>(
    () => ({
      bannerEnabled,
      bannerText,
      policyUrl,
      showRejectButton,
      decided,
      functional,
      analytics,
      showBanner,
      showSettings,
      openSettings: () => setShowSettings(true),
      closeSettings: () => setShowSettings(false),
      acceptAll,
      rejectOptional,
      savePreferences,
    }),
    [
      acceptAll,
      analytics,
      bannerEnabled,
      bannerText,
      decided,
      functional,
      policyUrl,
      rejectOptional,
      savePreferences,
      showBanner,
      showRejectButton,
      showSettings,
    ]
  );

  return <CookieConsentContext.Provider value={value}>{children}</CookieConsentContext.Provider>;
};

export function useCookieConsent(): CookieConsentContextValue {
  const context = useContext(CookieConsentContext);
  if (!context) {
    throw new Error('useCookieConsent must be used within CookieConsentProvider');
  }
  return context;
}

export function useCookieConsentOptional(): CookieConsentContextValue | null {
  return useContext(CookieConsentContext);
}
