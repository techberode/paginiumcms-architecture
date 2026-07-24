import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { ResponsiveLayout } from '../components/layout/ResponsiveLayout';

vi.mock('../hooks/useAuth', () => ({
  useAuth: () => ({
    user: { id: 'user_1', role: 'ADMIN' },
    twoFactorSetupPending: false,
  }),
}));

vi.mock('../hooks/useAdminCounts', () => ({
  useAdminCounts: () => ({ counts: null, showListCounts: false, refresh: vi.fn() }),
}));

vi.mock('../hooks/useOpenLinksInNewTab', () => ({
  useOpenLinksInNewTab: () => false,
}));

vi.mock('../components/backend/AdminSidebar', () => ({
  AdminSidebar: () => <nav data-testid="sidebar">Sidebar</nav>,
}));

vi.mock('../components/backend/AdminHeader', () => ({
  AdminHeader: () => <header data-testid="header">Header</header>,
}));

vi.mock('../components/backend/AdminCommandPalette', () => ({
  AdminCommandPalette: () => null,
}));

vi.mock('../components/backend/DemoModeBanner', () => ({
  DemoModeBanner: () => null,
}));

vi.mock('../components/auth/ChangePasswordModal', () => ({
  ChangePasswordModal: () => null,
}));

describe('admin route transitions (It.53)', () => {
  it('keeps admin shell mounted without hard reload', () => {
    const reloadSpy = vi.spyOn(window.location, 'reload').mockImplementation(() => undefined);
    const queryClient = new QueryClient({
      defaultOptions: { queries: { retry: false } },
    });

    render(
      <QueryClientProvider client={queryClient}>
        <MemoryRouter initialEntries={['/pages']}>
          <Routes>
            <Route
              path="/*"
              element={
                <ResponsiveLayout>
                  <div data-testid="route-content">Pages</div>
                </ResponsiveLayout>
              }
            />
          </Routes>
        </MemoryRouter>
      </QueryClientProvider>
    );

    expect(screen.getByTestId('sidebar')).toBeInTheDocument();
    expect(screen.getByTestId('route-content')).toBeInTheDocument();
    expect(reloadSpy).not.toHaveBeenCalled();
    reloadSpy.mockRestore();
  });
});
