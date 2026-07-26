// frontend/src/test/renderWithProviders.tsx
import { render, type RenderOptions } from '@testing-library/react';
import { TestI18nProvider } from '../context/I18nContext';
import { TestSettingsProvider } from '../context/SettingsContext';
import type { Locale } from '../i18n';

export type RenderWithProvidersOptions = Omit<RenderOptions, 'wrapper'> & {
  locale?: Locale;
};

export function renderWithProviders(
  ui: React.ReactElement,
  { locale = 'sk', ...options }: RenderWithProvidersOptions = {}
) {
  return render(ui, {
    ...options,
    wrapper: ({ children }) => (
      <TestI18nProvider locale={locale}>
        <TestSettingsProvider>{children}</TestSettingsProvider>
      </TestI18nProvider>
    ),
  });
}
