import { describe, expect, it } from 'vitest';
import type { Comment } from '../api/comments';
import { commentThreadReplies, commentThreadRootId, rootComments } from './commentThread';

const root: Comment = {
  id: 'comment_1',
  articleSlug: 'hello',
  author: 'Reader',
  content: 'Please clarify',
  status: 'approved',
  createdAt: '2026-09-19T00:00:00+00:00',
};

const reply: Comment = {
  id: 'comment_2',
  articleSlug: 'hello',
  author: 'Ada',
  content: 'Updated.',
  status: 'approved',
  createdAt: '2026-09-19T00:01:00+00:00',
  parentId: 'comment_1',
  authorUserId: 'user_1',
  staffReply: true,
};

describe('commentThread', () => {
  it('keeps inbox rows on the root comment and groups replies', () => {
    expect(rootComments([root, reply])).toEqual([root]);
    expect(commentThreadReplies([root, reply], 'comment_1')).toEqual([reply]);
    expect(commentThreadRootId([root, reply], 'comment_2')).toBe('comment_1');
    expect(commentThreadRootId([root, reply], 'comment_1')).toBe('comment_1');
  });
});
