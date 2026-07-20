import { describe, expect, it, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import { MemoryRouter, Route, Routes, Link } from 'react-router-dom';
import { ResponsiveLayout } from './ResponsiveLayout';

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ twoFactorSetupPending: false }),
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

    render(
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
});
