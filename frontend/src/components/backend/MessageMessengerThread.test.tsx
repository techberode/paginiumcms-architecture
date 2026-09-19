import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MessageMessengerThread } from './MessageMessengerThread';
import { renderWithProviders } from '../../test/renderWithProviders';
import { messagesApi, type ContactMessage } from '../../api/messages';

vi.mock('../../api/messages', () => ({
  messagesApi: {
    reply: vi.fn(),
    claim: vi.fn(),
    release: vi.fn(),
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() }),
}));

const base: ContactMessage = {
  id: 'msg_1',
  path: 'data/messages/msg_1.json',
  name: 'Jane',
  email: 'jane@example.com',
  subject: 'Technická podpora',
  message: 'Notebook sa nespustí.',
  createdAt: '2026-09-19T00:00:00+00:00',
  isRead: false,
  isProcessed: false,
  isArchived: false,
  priority: 'normal',
  handleStatus: 'open',
  canClaim: true,
  canReply: true,
  thread: [],
};

describe('MessageMessengerThread', () => {
  it('sends a staff reply through the Messenger composer', async () => {
    vi.mocked(messagesApi.reply).mockResolvedValue({
      ...base,
      handleStatus: 'in_progress',
      claimedBy: 'user_1',
      claimedByName: 'Ada',
      thread: [
        {
          id: 'rep_1',
          authorType: 'staff',
          authorName: 'Ada',
          body: 'Skús safe mode.',
          createdAt: '2026-09-19T00:01:00+00:00',
        },
      ],
    });

    const onUpdated = vi.fn();
    renderWithProviders(<MessageMessengerThread message={base} onUpdated={onUpdated} />, { locale: 'en' });

    fireEvent.change(screen.getByPlaceholderText('Write a reply…'), { target: { value: 'Skús safe mode.' } });
    fireEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => {
      expect(messagesApi.reply).toHaveBeenCalledWith('msg_1', 'Skús safe mode.');
    });
    expect(onUpdated).toHaveBeenCalled();
  });

  it('hides the composer while the Desk bubble is the active chat', () => {
    renderWithProviders(
      <MessageMessengerThread message={base} composerEnabled={false} onUpdated={vi.fn()} />,
      { locale: 'en' }
    );

    expect(screen.queryByPlaceholderText('Write a reply…')).not.toBeInTheDocument();
    expect(screen.getByText('Notebook sa nespustí.')).toBeInTheDocument();
  });
});
