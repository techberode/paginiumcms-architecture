// frontend/src/test/renderWithRouter.tsx
import { render, type RenderOptions } from '@testing-library/react';
import { MemoryRouter, type MemoryRouterProps } from 'react-router-dom';
import { TestI18nProvider } from '../context/I18nContext';

const routerFuture: MemoryRouterProps['future'] = {
  v7_startTransition: true,
  v7_relativeSplatPath: true,
};

type RenderWithRouterOptions = Omit<RenderOptions, 'wrapper'> & {
  routerProps?: Omit<MemoryRouterProps, 'future'>;
};

export function renderWithRouter(
  ui: React.ReactElement,
  { routerProps, ...options }: RenderWithRouterOptions = {}
) {
  return render(ui, {
    ...options,
    wrapper: ({ children }) => (
      <TestI18nProvider>
        <MemoryRouter future={routerFuture} {...routerProps}>
          {children}
        </MemoryRouter>
      </TestI18nProvider>
    ),
  });
}
