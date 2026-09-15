import { describe, expect, it, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { StaffDirectory } from './StaffDirectory';
import { renderWithProviders } from '../../test/renderWithProviders';
import { fetchPublicStaff } from '../../api/staff';

vi.mock('../../api/staff', () => ({
  fetchPublicStaff: vi.fn(),
}));

describe('StaffDirectory', () => {
  beforeEach(() => {
    vi.mocked(fetchPublicStaff).mockResolvedValue({
      contacts: [
        {
          id: 'user_1',
          name: 'Ada Lovelace',
          jobTitle: 'Editor',
          email: 'ada@example.com',
          socials: [{ platform: 'telegram', label: 'Chat', url: 'https://t.me/ada', directChat: true }],
        },
      ],
      support: [],
    });
  });

  it('renders opted-in contact cards from the public staff API', async () => {
    renderWithProviders(<StaffDirectory />);
    expect(await screen.findByTestId('staff-directory')).toBeInTheDocument();
    expect(screen.getByText('Ada Lovelace')).toBeInTheDocument();
    expect(screen.getByText('ada@example.com')).toBeInTheDocument();
    expect(screen.getByRole('link', { name: /chat|priamy chat/i })).toHaveAttribute('href', 'https://t.me/ada');
  });

  it('renders nothing when nobody opted in', async () => {
    vi.mocked(fetchPublicStaff).mockResolvedValue({ contacts: [], support: [] });
    const { container } = renderWithProviders(<StaffDirectory />);
    await waitFor(() => {
      expect(fetchPublicStaff).toHaveBeenCalled();
    });
    expect(container.querySelector('[data-testid="staff-directory"]')).toBeNull();
  });
});
