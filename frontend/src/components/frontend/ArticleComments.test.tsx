import { describe, expect, it, vi, beforeEach } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { ArticleComments } from './ArticleComments';
import { renderWithProviders } from '../../test/renderWithProviders';
import { listPublicComments, replyToComment } from '../../api/comments';
import { authApi } from '../../api/auth';

vi.mock('../../api/comments', () => ({
  listPublicComments: vi.fn(),
  submitComment: vi.fn(),
  replyToComment: vi.fn(),
}));

vi.mock('../../api/auth', () => ({
  authApi: {
    chatStatus: vi.fn(),
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() }),
}));

vi.mock('../../hooks/useAuth', () => ({
  useAuth: () => ({ user: { id: 'user_1', name: 'Ada' } }),
}));

describe('ArticleComments', () => {
  beforeEach(() => {
    vi.mocked(listPublicComments).mockResolvedValue([
      {
        id: 'comment_1',
        articleSlug: 'hello',
        author: 'Reader',
        content: 'Nice article',
        status: 'approved',
        createdAt: '2026-09-19T00:00:00+00:00',
        replies: [],
      },
    ]);
    vi.mocked(authApi.chatStatus).mockResolvedValue({
      success: true,
      data: {
        chatEnabled: true,
        online: false,
        lastSeen: 0,
        inSupportTeam: true,
        canPublicChat: true,
        canReplyComments: true,
      },
    });
  });

  it('lets a team member reply under the public comment', async () => {
    vi.mocked(replyToComment).mockResolvedValue({
      ok: true,
      comment: {
        id: 'comment_2',
        articleSlug: 'hello',
        author: 'Ada',
        content: 'Thanks — we will update that section.',
        status: 'approved',
        createdAt: '2026-09-19T00:01:00+00:00',
        parentId: 'comment_1',
        staffReply: true,
      },
    });

    renderWithProviders(
      <MemoryRouter>
        <ArticleComments articleSlug="hello" enabled requireApproval={false} />
      </MemoryRouter>,
      { locale: 'en' }
    );

    fireEvent.click(await screen.findByRole('button', { name: 'Reply on the page' }));
    fireEvent.change(screen.getByPlaceholderText('Write a public reply…'), {
      target: { value: 'Thanks — we will update that section.' },
    });
    fireEvent.click(screen.getAllByRole('button', { name: 'Reply on the page' })[0]);

    await waitFor(() => {
      expect(replyToComment).toHaveBeenCalledWith('comment_1', 'Thanks — we will update that section.');
    });
    expect(await screen.findByText('Thanks — we will update that section.')).toBeInTheDocument();
  });
});
