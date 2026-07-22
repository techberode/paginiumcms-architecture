// frontend/src/test/renderWithRouter.tsx
import { MemoryRouter, type MemoryRouterProps } from 'react-router-dom';
import { renderWithProviders, type RenderWithProvidersOptions } from './renderWithProviders';

const routerFuture: MemoryRouterProps['future'] = {
  v7_startTransition: true,
  v7_relativeSplatPath: true,
};

type RenderWithRouterOptions = RenderWithProvidersOptions & {
  routerProps?: Omit<MemoryRouterProps, 'future' | 'children'>;
};

export function renderWithRouter(
  ui: React.ReactElement,
  { routerProps, locale, ...options }: RenderWithRouterOptions = {}
) {
  return renderWithProviders(
    <MemoryRouter future={routerFuture} {...routerProps}>
      {ui}
    </MemoryRouter>,
    { locale, ...options }
  );
}
