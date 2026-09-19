import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { StaffDirectory } from './StaffDirectory';
import { renderWithProviders } from '../../test/renderWithProviders';
import { fetchPublicStaff, sendStaffMessage } from '../../api/staff';

vi.mock('../../api/staff', () => ({
  fetchPublicStaff: vi.fn(),
  fetchStaffCards: vi.fn(),
  sendStaffMessage: vi.fn(),
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
          chatEnabled: true,
          online: true,
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

  it('sends a staff-card chat into Messages', async () => {
    vi.mocked(sendStaffMessage).mockResolvedValue({ success: true });
    renderWithProviders(<StaffDirectory />, { locale: 'en' });

    fireEvent.click(await screen.findByTestId('staff-chat-toggle-user_1'));
    fireEvent.change(screen.getByPlaceholderText('Your name'), { target: { value: 'Visitor' } });
    fireEvent.change(screen.getByPlaceholderText('Email'), { target: { value: 'visitor@example.com' } });
    fireEvent.change(screen.getByPlaceholderText(/message/i), { target: { value: 'Please help me with my account.' } });
    fireEvent.click(screen.getByRole('button', { name: /send to messages/i }));

    await waitFor(() => {
      expect(sendStaffMessage).toHaveBeenCalledWith('user_1', {
        name: 'Visitor',
        email: 'visitor@example.com',
        message: 'Please help me with my account.',
      });
    });
    expect(await screen.findByText('Message saved to the site inbox.')).toBeInTheDocument();
  });
});
