// frontend/src/components/backend/TrashManager.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, fireEvent, waitFor } from '@testing-library/react';
import { TrashManager } from './TrashManager';
import { renderWithRouter } from '../../test/renderWithRouter';

const mocks = vi.hoisted(() => ({
  list: vi.fn(),
  restore: vi.fn(),
}));

vi.mock('../../api/trash', () => ({
  trashApi: {
    list: mocks.list,
    restore: mocks.restore,
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

vi.mock('../../hooks/useAdminListPageSize', () => ({
  useAdminListPageSize: () => [20, vi.fn()],
}));

const sampleItem = {
  id: 'trash_abc',
  originalPath: 'pages/home.md',
  deletedAt: '2026-07-17T08:00:00+02:00',
  filename: 'trash_abc_home.md',
  size: 2048,
};

describe('TrashManager', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.list.mockResolvedValue([sampleItem]);
    mocks.restore.mockResolvedValue({ originalPath: 'pages/home.md' });
    vi.stubGlobal('confirm', vi.fn(() => true));
  });

  it('renders trash items after load', async () => {
    renderWithRouter(<TrashManager />);

    expect(await screen.findByText('pages/home.md')).toBeInTheDocument();
    expect(mocks.list).toHaveBeenCalled();
  });

  it('shows empty state when trash is empty', async () => {
    mocks.list.mockResolvedValue([]);
    renderWithRouter(<TrashManager />);

    expect(await screen.findByText('Kôš je prázdny.')).toBeInTheDocument();
  });

  it('calls restore API on button click', async () => {
    renderWithRouter(<TrashManager />);

    const button = await screen.findByRole('button', { name: /Obnoviť/i });
    fireEvent.click(button);

    await waitFor(() => {
      expect(mocks.restore).toHaveBeenCalledWith('trash_abc');
    });
  });
});
