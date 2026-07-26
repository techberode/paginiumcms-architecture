// frontend/src/components/backend/AdminCommandPalette.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, fireEvent } from '@testing-library/react';
import { AdminCommandPalette } from './AdminCommandPalette';
import { renderWithRouter } from '../../test/renderWithRouter';

// 1. Obalenie mocku navigácie do vi.hoisted, aby bol dostupný pred importami
const { mockNavigate } = vi.hoisted(() => {
  return {
    mockNavigate: vi.fn()
  };
});

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
    renderWithRouter(<AdminCommandPalette isOpen onClose={() => undefined} />);

    expect(screen.getByPlaceholderText(/Ctrl\+K/i)).toBeInTheDocument();
  });

  it('navigates on result click after search', async () => {
    renderWithRouter(<AdminCommandPalette isOpen onClose={() => undefined} />);

    fireEvent.change(screen.getByPlaceholderText(/Ctrl\+K/i), { target: { value: 'set' } });

    expect(await screen.findByText('Nastavenia')).toBeInTheDocument();

    fireEvent.click(screen.getByText('Nastavenia'));
    expect(mockNavigate).toHaveBeenCalledWith('/settings');
  });
});

