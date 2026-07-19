// frontend/src/components/backend/AdminCommandPalette.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { AdminCommandPalette } from './AdminCommandPalette';

const mockNavigate = vi.fn();

vi.mock('react-router-dom', async () => {
  const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
  return {
    ...actual,
    useNavigate: () => mockNavigate,
  };
});

vi.mock('../../api/search', () => ({
  searchAdmin: vi.fn().mockResolvedValue({
    query: 'set',
    scope: 'admin',
    results: [
      {
        type: 'route',
        title: 'Nastavenia',
        subtitle: 'Admin modul',
        path: '/settings',
        adminPath: '/settings',
      },
    ],
    counts: { route: 1, page: 0, article: 0, media: 0 },
  }),
}));

describe('AdminCommandPalette', () => {
  beforeEach(() => {
    mockNavigate.mockReset();
    localStorage.clear();
  });

  it('renders search input when open', () => {
    render(
      <MemoryRouter>
        <AdminCommandPalette isOpen onClose={() => undefined} />
      </MemoryRouter>
    );

    expect(screen.getByPlaceholderText(/Ctrl\+K/i)).toBeInTheDocument();
  });

  it('navigates on result click after search', async () => {
    render(
      <MemoryRouter>
        <AdminCommandPalette isOpen onClose={() => undefined} />
      </MemoryRouter>
    );

    fireEvent.change(screen.getByPlaceholderText(/Ctrl\+K/i), { target: { value: 'set' } });

    expect(await screen.findByText('Nastavenia')).toBeInTheDocument();

    fireEvent.click(screen.getByText('Nastavenia'));
    expect(mockNavigate).toHaveBeenCalledWith('/settings');
  });
});
