import { describe, expect, it } from 'vitest';
import { MemoryRouter } from 'react-router-dom';
import { screen } from '@testing-library/react';
import { FileText } from 'lucide-react';
import { renderWithProviders } from '../../test/renderWithProviders';
import { AdminKpiCard } from './AdminKpiCard';
import { AdminWidgetCard } from './AdminWidgetCard';
import { AdminOfferCard } from './AdminOfferCard';
import { AdminTabs } from './AdminTabs';
import { AdminDataTable } from './AdminDataTable';
import { AdminToolbar } from './AdminToolbar';

describe('admin kit', () => {
  it('renders a linked KPI card', () => {
    renderWithProviders(
      <MemoryRouter>
        <AdminKpiCard title="Pages" value={12} icon={FileText} to="/pages" footer={<span>today</span>} />
      </MemoryRouter>
    );

    expect(screen.getByRole('link', { name: /pages/i })).toHaveAttribute('href', '/pages');
    expect(screen.getByText('12')).toBeTruthy();
    expect(screen.getByText('today')).toBeTruthy();
  });

  it('renders widget header and toolbar', () => {
    renderWithProviders(
      <AdminWidgetCard title="Visits" action={<button type="button">Open</button>}>
        <AdminToolbar>
          <span>left</span>
          <span>right</span>
        </AdminToolbar>
      </AdminWidgetCard>
    );

    expect(screen.getByRole('heading', { name: 'Visits' })).toBeTruthy();
    expect(screen.getByRole('button', { name: 'Open' })).toBeTruthy();
    expect(screen.getByText('left')).toBeTruthy();
  });

  it('renders a selectable offer card', () => {
    renderWithProviders(
      <AdminOfferCard title="Support" subtitle="1 member" icon={FileText} active testId="offer-support" />
    );

    const card = screen.getByTestId('offer-support');
    expect(card).toHaveTextContent('Support');
    expect(card).toHaveTextContent('1 member');
  });

  it('renders underline tabs for the active section', () => {
    renderWithProviders(
      <AdminTabs
        activeId="public"
        items={[
          { id: 'profile', label: 'Profil' },
          { id: 'public', label: 'Verejná karta', testId: 'tab-public' },
        ]}
        onSelect={() => undefined}
      />
    );

    expect(screen.getByTestId('tab-public')).toHaveClass('admin-tab-on');
    expect(screen.getByRole('button', { name: 'Profil' })).not.toHaveClass('admin-tab-on');
  });

  it('renders table columns', () => {
    renderWithProviders(
      <AdminDataTable columns={['Name', 'Status']} empty="No rows">
        <tr>
          <td>Home</td>
          <td>published</td>
        </tr>
      </AdminDataTable>
    );

    expect(screen.getByText('Name')).toBeTruthy();
    expect(screen.getByText('Home')).toBeTruthy();
  });
});
