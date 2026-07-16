// frontend/src/context/I18nContext.tsx
import React, { createContext, useContext, useMemo } from 'react';
import { normalizeLocale, translate, type Locale } from '../i18n';
import { useSettings } from '../hooks/useSettings';

interface I18nContextValue {
  locale: Locale;
  t: (key: string, params?: Record<string, string | number>) => string;
}

const I18nContext = createContext<I18nContextValue | undefined>(undefined);

export const I18nProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { get } = useSettings();
  const locale = normalizeLocale(get('general.language', 'sk'));

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
