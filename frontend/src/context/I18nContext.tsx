// frontend/src/context/I18nContext.tsx
import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { loadRuntimeI18nOverrides } from '../i18n/loadRuntimeOverrides';
import { normalizeLocale, translate, type Locale } from '../i18n';
import { useSettings } from '../hooks/useSettings';
import { AuthContext } from './AuthContext';

interface I18nContextValue {
  locale: Locale;
  t: (key: string, params?: Record<string, string | number>) => string;
}

const I18nContext = createContext<I18nContextValue | undefined>(undefined);

export const I18nProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { get } = useSettings();
  const auth = useContext(AuthContext);
  const siteLocale = normalizeLocale(get('general.language', 'sk'));
  const preferred = typeof auth?.user?.locale === 'string' ? auth.user.locale.trim() : '';
  const locale = preferred === 'sk' || preferred === 'en' ? preferred : siteLocale;
  const [runtimeRevision, setRuntimeRevision] = useState(0);

  useEffect(() => {
    let cancelled = false;

    const applyRuntimeOverrides = async (): Promise<void> => {
      await loadRuntimeI18nOverrides(locale);
      if (!cancelled) {
        setRuntimeRevision((value) => value + 1);
      }
    };

    void applyRuntimeOverrides();

    const onReload = (): void => {
      void applyRuntimeOverrides();
    };
    window.addEventListener('paginium:i18n-runtime-reload', onReload);

    return () => {
      cancelled = true;
      window.removeEventListener('paginium:i18n-runtime-reload', onReload);
    };
  }, [locale]);

  const value = useMemo<I18nContextValue>(
    () => ({
      locale,
      t: (key, params) => translate(locale, key, params),
    }),
    [locale, runtimeRevision]
  );

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
};

/** Test-only provider without SettingsContext dependency. */
export const TestI18nProvider: React.FC<{
  children: React.ReactNode;
  locale?: Locale;
}> = ({ children, locale = 'sk' }) => {
  const value = useMemo<I18nContextValue>(
    () => ({
      locale,
      t: (key, params) => translate(locale, key, params),
    }),
    [locale]
  );

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
};

export function useI18n(): I18nContextValue {
  const ctx = useContext(I18nContext);
  if (!ctx) {
    throw new Error('useI18n must be used within I18nProvider');
  }

  return ctx;
}
