// frontend/src/test/renderWithRouter.tsx
import { render, type RenderOptions } from '@testing-library/react';
import { MemoryRouter, type MemoryRouterProps } from 'react-router-dom';
import { TestI18nProvider } from '../context/I18nContext';
import { TestSettingsProvider } from '../context/SettingsContext';
import { ConfirmProvider } from '../context/ConfirmContext';
import type { Locale } from '../i18n';

type RenderWithRouterOptions = Omit<RenderOptions, 'wrapper'> & {
  locale?: Locale;
  routerProps?: Omit<MemoryRouterProps, 'children'>;
};

export function renderWithRouter(
  ui: React.ReactElement,
  { routerProps, locale = 'sk', ...options }: RenderWithRouterOptions = {}
) {
  return render(ui, {
    ...options,
    wrapper: ({ children }) => (
      <MemoryRouter {...routerProps}>
        <TestI18nProvider locale={locale}>
          <TestSettingsProvider>
            <ConfirmProvider>{children}</ConfirmProvider>
          </TestSettingsProvider>
        </TestI18nProvider>
      </MemoryRouter>
    ),
  });
}
