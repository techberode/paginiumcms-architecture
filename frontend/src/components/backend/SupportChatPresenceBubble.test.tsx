import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { SupportChatPresenceBubble } from './SupportChatPresenceBubble';
import { renderWithProviders } from '../../test/renderWithProviders';
import { authApi } from '../../api/auth';

vi.mock('../../api/auth', () => ({
  authApi: {
    desk: vi.fn(),
    updatePresence: vi.fn(),
    updateProfile: vi.fn(),
  },
}));

vi.mock('../../api/comments', () => ({
  replyToComment: vi.fn(),
  claimComment: vi.fn(),
}));

vi.mock('../../api/messages', () => ({
  messagesApi: { reply: vi.fn(), claim: vi.fn() },
}));

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ user: { id: 'user_1', name: 'Ada' }, updateUser: vi.fn() }),
}));

describe('SupportChatPresenceBubble', () => {
  beforeEach(() => {
    vi.mocked(authApi.desk).mockResolvedValue({
      success: true,
      data: {
        chatEnabled: true,
        online: true,
        lastSeen: 1,
        inSupportTeam: true,
        canPublicChat: true,
        hasDesk: true,
        canReplyComments: true,
        deskCount: 1,
        deskBubbleEnabled: true,
        deskBubbleAnchor: 'right',
        items: [
          {
            kind: 'comment',
            id: 'comment_1',
            title: 'Reader',
            preview: 'Nice article',
            href: '/comments#comment-comment_1',
            articleSlug: 'hello',
            createdAt: '2026-09-19T00:00:00+00:00',
            mine: true,
          },
        ],
      },
    });
  });

  it('shows the desk count and opens the queue', async () => {
    renderWithProviders(
      <MemoryRouter>
        <SupportChatPresenceBubble />
      </MemoryRouter>,
      { locale: 'en' }
    );

    expect(await screen.findByTestId('desk-count')).toHaveTextContent('1');
    fireEvent.click(screen.getByTestId('support-chat-bubble'));
    expect(await screen.findByTestId('desk-queue')).toHaveTextContent('Nice article');
    fireEvent.click(screen.getByText('Reader'));
    expect(screen.getByRole('button', { name: 'Open on the page' })).toBeInTheDocument();
    expect(screen.getByTestId('desk-pop-out')).toBeDisabled();
    expect(screen.getByTestId('desk-queue')).toHaveClass('desk-solid-panel');
    expect(screen.getByTestId('desk-drag')).toBeInTheDocument();
    expect(screen.getByTestId('support-chat-bubble-wrap')).toHaveClass('z-[300]');
    await waitFor(() => {
      expect(authApi.desk).toHaveBeenCalled();
    });
  });

  it('hides when the account switch is off', async () => {
    vi.mocked(authApi.desk).mockResolvedValue({
      success: true,
      data: {
        chatEnabled: true,
        online: true,
        lastSeen: 1,
        inSupportTeam: true,
        canPublicChat: true,
        hasDesk: true,
        canReplyComments: true,
        deskCount: 1,
        deskBubbleEnabled: false,
        items: [],
      },
    });

    renderWithProviders(
      <MemoryRouter>
        <SupportChatPresenceBubble />
      </MemoryRouter>,
      { locale: 'en' }
    );

    await waitFor(() => {
      expect(authApi.desk).toHaveBeenCalled();
    });
    expect(screen.queryByTestId('support-chat-bubble')).not.toBeInTheDocument();
  });
});
