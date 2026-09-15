import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, within } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { LayoutDashboard, FileText } from 'lucide-react';
import { AdminTopNav } from './AdminTopNav';
import { renderWithProviders } from '../../test/renderWithProviders';

vi.mock('../../hooks/useAdminNavModel', () => ({
  useAdminNavModel: () => ({
    visibleSections: [
      {
        id: 'workspace',
        labelKey: 'admin.sections.workspace',
        items: [{ id: 'pages', labelKey: 'admin.nav.pages', href: '/pages', icon: FileText }],
      },
    ],
    primaryItems: [{ id: 'dashboard', labelKey: 'admin.nav.dashboard', href: '/dashboard', icon: LayoutDashboard }],
    openSections: {},
    setOpenSections: vi.fn(),
    countFor: () => undefined,
    isItemActive: (href: string) => href === '/dashboard',
    showListCounts: false,
  }),
}));

describe('AdminTopNav', () => {
  it('expands a section in the mobile accordion', () => {
    renderWithProviders(
      <MemoryRouter>
        <AdminTopNav mobileOpen onNavigate={() => undefined} />
      </MemoryRouter>
    );

    const mobile = screen.getByTestId('admin-topnav-mobile');
    fireEvent.click(within(mobile).getByRole('button', { name: /workspace|pracovný/i }));
    expect(within(mobile).getByRole('link', { name: /pages|stránky/i })).toBeInTheDocument();
  });

  it('opens the desktop dropdown as a fixed overlay', () => {
    renderWithProviders(
      <MemoryRouter>
        <div className="admin-shell">
          <AdminTopNav mobileOpen={false} onNavigate={() => undefined} />
        </div>
      </MemoryRouter>
    );

    const desktop = screen.getByTestId('admin-topnav-desktop');
    expect(desktop.className).not.toMatch(/overflow-x-auto/);

    fireEvent.click(within(desktop).getByRole('button', { hidden: true, name: /workspace|pracovný/i }));
    const menu = screen.getByTestId('admin-topnav-menu');
    expect(menu).toHaveClass('fixed');
    expect(menu).toHaveClass('admin-topnav-menu');
    expect(within(menu).getByRole('link', { name: /pages|stránky/i })).toBeInTheDocument();
  });
});
