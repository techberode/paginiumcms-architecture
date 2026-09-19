import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import { DeskQueueItemMeta } from './DeskQueueItemMeta';
import { renderWithProviders } from '../../test/renderWithProviders';
import type { DeskItem } from '../../api/auth';

const comment: DeskItem = {
  kind: 'comment',
  id: 'comment_1',
  title: 'Reader',
  preview: 'Please clarify',
  href: '/comments#comment-comment_1',
  createdAt: '2026-09-19T00:00:00+00:00',
  mine: true,
  handleStatus: 'in_progress',
};

const message: DeskItem = {
  kind: 'message',
  id: 'msg_1',
  title: 'Support',
  preview: 'Notebook',
  href: '/messages#message-msg_1',
  createdAt: '2026-09-19T00:00:00+00:00',
  priority: 'urgent',
};

describe('DeskQueueItemMeta', () => {
  it('marks a comment versus a message and shows reply priority', () => {
    const { rerender } = renderWithProviders(<DeskQueueItemMeta item={comment} />, { locale: 'en' });
    expect(screen.getByTestId('desk-item-meta-comment-comment_1')).toHaveTextContent('Comment');
    expect(screen.getByTestId('desk-item-meta-comment-comment_1')).toHaveTextContent('Reply under article');
    expect(screen.getByTestId('desk-item-meta-comment-comment_1')).toHaveTextContent('Your desk');
    expect(screen.getByTestId('desk-item-meta-comment-comment_1')).toHaveTextContent('In progress');

    rerender(<DeskQueueItemMeta item={message} />);
    expect(screen.getByTestId('desk-item-meta-message-msg_1')).toHaveTextContent('Message');
    expect(screen.getByTestId('desk-item-meta-message-msg_1')).toHaveTextContent('Urgent');
  });
});
