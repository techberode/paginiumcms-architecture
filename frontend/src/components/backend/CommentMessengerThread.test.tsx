import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import { CommentMessengerThread } from './CommentMessengerThread';
import { renderWithProviders } from '../../test/renderWithProviders';
import { replyToComment, type Comment } from '../../api/comments';

vi.mock('../../api/comments', () => ({
  replyToComment: vi.fn(),
  claimComment: vi.fn(),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({ success: vi.fn(), error: vi.fn(), warning: vi.fn(), info: vi.fn() }),
}));

const root: Comment = {
  id: 'comment_1',
  articleSlug: 'hello',
  author: 'Reader',
  content: 'Please clarify the second paragraph.',
  status: 'approved',
  createdAt: '2026-09-19T00:00:00+00:00',
};

describe('CommentMessengerThread', () => {
  it('sends a staff reply under the article comment when the in-page composer is on', async () => {
    vi.mocked(replyToComment).mockResolvedValue({
      ok: true,
      comment: {
        id: 'comment_2',
        articleSlug: 'hello',
        author: 'Ada',
        content: 'The second paragraph is the API contract.',
        status: 'approved',
        createdAt: '2026-09-19T00:01:00+00:00',
        parentId: 'comment_1',
        staffReply: true,
      },
    });

    const onUpdated = vi.fn();
    renderWithProviders(
      <MemoryRouter>
        <CommentMessengerThread
          comment={root}
          replies={[]}
          composerEnabled
          canReply
          onUpdated={onUpdated}
        />
      </MemoryRouter>,
      { locale: 'en' }
    );

    fireEvent.change(screen.getByTestId('comment-thread-input-comment_1'), {
      target: { value: 'The second paragraph is the API contract.' },
    });
    fireEvent.click(screen.getByRole('button', { name: 'Send' }));

    await waitFor(() => {
      expect(replyToComment).toHaveBeenCalledWith('comment_1', 'The second paragraph is the API contract.');
    });
    expect(onUpdated).toHaveBeenCalled();
  });

  it('can approve a pending comment from the thread', () => {
    const onApprove = vi.fn();
    renderWithProviders(
      <MemoryRouter>
        <CommentMessengerThread
          comment={{ ...root, status: 'pending' }}
          replies={[]}
          composerEnabled={false}
          canReply
          onApprove={onApprove}
          onUpdated={vi.fn()}
        />
      </MemoryRouter>,
      { locale: 'en' }
    );

    fireEvent.click(screen.getByTestId('comment-thread-approve-comment_1'));
    expect(onApprove).toHaveBeenCalled();
  });

  it('hides the composer while the Desk bubble is the active chat', () => {
    renderWithProviders(
      <MemoryRouter>
        <CommentMessengerThread
          comment={root}
          replies={[]}
          composerEnabled={false}
          canReply
          onUpdated={vi.fn()}
        />
      </MemoryRouter>,
      { locale: 'en' }
    );

    expect(screen.queryByTestId('comment-thread-input-comment_1')).not.toBeInTheDocument();
    expect(screen.getByText('Please clarify the second paragraph.')).toBeInTheDocument();
  });
});
