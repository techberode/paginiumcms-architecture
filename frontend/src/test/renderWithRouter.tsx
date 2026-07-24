// frontend/src/test/renderWithRouter.tsx
import { MemoryRouter, type MemoryRouterProps } from 'react-router-dom';
import { renderWithProviders, type RenderWithProvidersOptions } from './renderWithProviders';

type RenderWithRouterOptions = RenderWithProvidersOptions & {
  routerProps?: Omit<MemoryRouterProps, 'children'>;
};

export function renderWithRouter(
  ui: React.ReactElement,
  { routerProps, locale, ...options }: RenderWithRouterOptions = {}
) {
  return renderWithProviders(
    <MemoryRouter {...routerProps}>
      {ui}
    </MemoryRouter>,
    { locale, ...options }
  );
}
