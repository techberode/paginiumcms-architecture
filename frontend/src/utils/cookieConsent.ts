export const COOKIE_CONSENT_STORAGE_KEY = 'paginium-cookie-consent';

export interface CookieConsentPreferences {
  necessary: true;
  functional: boolean;
  analytics: boolean;
}

export interface StoredCookieConsent extends CookieConsentPreferences {
  decidedAt: string;
}

export function readStoredCookieConsent(): StoredCookieConsent | null {
  if (typeof window === 'undefined') {
    return null;
  }

  try {
    const raw = window.localStorage.getItem(COOKIE_CONSENT_STORAGE_KEY);
    if (!raw) {
      return null;
    }

    const parsed = JSON.parse(raw) as Partial<StoredCookieConsent>;
    if (typeof parsed !== 'object' || parsed === null) {
      return null;
    }

    return {
      necessary: true,
      functional: parsed.functional === true,
      analytics: parsed.analytics === true,
      decidedAt: typeof parsed.decidedAt === 'string' ? parsed.decidedAt : new Date().toISOString(),
    };
  } catch {
    return null;
  }
}

export function writeStoredCookieConsent(preferences: CookieConsentPreferences): StoredCookieConsent {
  const payload: StoredCookieConsent = {
    necessary: true,
    functional: preferences.functional,
    analytics: preferences.analytics,
    decidedAt: new Date().toISOString(),
  };

  window.localStorage.setItem(COOKIE_CONSENT_STORAGE_KEY, JSON.stringify(payload));
  return payload;
}

export function clearFunctionalStorageIfDenied(): void {
  const stored = readStoredCookieConsent();
  if (stored && !stored.functional) {
    window.localStorage.removeItem('paginium-public-theme');
  }
}
