import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes, Link } from 'react-router-dom';
import { ResponsiveLayout } from './ResponsiveLayout';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ twoFactorSetupPending: false }),
}));

vi.mock('../../context/ThemeContext', () => ({
  useTheme: () => ({
    isDark: false,
    theme: 'light',
    setTheme: () => undefined,
    toggleTheme: () => undefined,
  }),
}));

vi.mock('../../hooks/useMediaQuery', () => ({
  useMediaQuery: () => false,
}));

vi.mock('../../hooks/useOpenLinksInNewTab', () => ({
  useOpenLinksInNewTab: () => false,
}));

vi.mock('../backend/AdminSidebar', () => ({
  AdminSidebar: () => <div>Sidebar</div>,
}));

vi.mock('../backend/AdminHeader', () => ({
  AdminHeader: () => <div>Header</div>,
}));

vi.mock('../backend/AdminCommandPalette', () => ({
  AdminCommandPalette: () => null,
}));

vi.mock('../backend/DemoModeBanner', () => ({
  DemoModeBanner: () => null,
}));

vi.mock('../auth/ChangePasswordModal', () => ({
  ChangePasswordModal: () => null,
}));

describe('ResponsiveLayout scroll restoration', () => {
  it('resets scroll container on pathname change', () => {
    const scrollTo = vi.spyOn(HTMLElement.prototype, 'scrollTo').mockImplementation(() => undefined);

    renderWithProviders(
      <MemoryRouter initialEntries={['/pages']}>
        <Routes>
          <Route
            path="*"
            element={
              <ResponsiveLayout>
                <Link to="/media">Go media</Link>
              </ResponsiveLayout>
            }
          />
        </Routes>
      </MemoryRouter>
    );

    fireEvent.click(screen.getByRole('link', { name: 'Go media' }));

    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'auto' });
    scrollTo.mockRestore();
  });

  it('keeps the admin header outside the scrolling pane', () => {
    renderWithProviders(
      <MemoryRouter initialEntries={['/pages']}>
        <Routes>
          <Route
            path="*"
            element={
              <ResponsiveLayout>
                <div>Page body</div>
              </ResponsiveLayout>
            }
          />
        </Routes>
      </MemoryRouter>
    );

    const header = screen.getByText('Header');
    const scrollPane = screen.getByTestId('admin-scroll-pane');

    expect(scrollPane).not.toContainElement(header);
    expect(scrollPane).toHaveTextContent('Page body');
    expect(screen.getByTestId('admin-shell')).toHaveAttribute('data-admin-nav', 'side');
  });
});
