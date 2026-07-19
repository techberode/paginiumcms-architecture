// frontend/src/api/comments.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest';
import {
  deleteComment,
  listAdminComments,
  listPublicComments,
  submitComment,
  updateCommentStatus,
} from './comments';

const mocks = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('./client', () => ({
  default: {
    get: mocks.get,
    post: mocks.post,
    put: mocks.put,
    delete: mocks.delete,
  },
}));

describe('comments API', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('listPublicComments filters by article slug', async () => {
    mocks.get.mockResolvedValue({
      success: true,
      data: [{ id: 'c1', articleSlug: 'post-a', author: 'A', content: 'Hi', status: 'approved', createdAt: '' }],
    });

    const items = await listPublicComments('post-a');
    expect(mocks.get).toHaveBeenCalledWith('/api/comments?articleSlug=post-a');
    expect(items).toHaveLength(1);
  });

  it('submitComment returns ok result', async () => {
    const comment = {
      id: 'c1',
      articleSlug: 'post-a',
      author: 'Jane',
      content: 'Nice post',
      status: 'pending' as const,
      createdAt: '2026-01-01',
    };
    mocks.post.mockResolvedValue({ success: true, data: comment });

    const result = await submitComment({
      articleSlug: 'post-a',
      author: 'Jane',
      content: 'Nice post',
    });
    expect(result).toEqual({ ok: true, comment });
  });

  it('listAdminComments reads items from admin response', async () => {
    mocks.get.mockResolvedValue({
      success: true,
      data: { items: [{ id: 'c1' }], count: 1 },
    });
    const items = await listAdminComments({ status: 'pending' });
    expect(mocks.get).toHaveBeenCalledWith('/api/admin/comments?status=pending');
    expect(items).toHaveLength(1);
  });

  it('updateCommentStatus and deleteComment return expected shapes', async () => {
    const comment = {
      id: 'c1',
      articleSlug: 'post-a',
      author: 'Jane',
      content: 'Nice post',
      status: 'approved' as const,
      createdAt: '2026-01-01',
    };
    mocks.put.mockResolvedValue({ success: true, data: comment });
    mocks.delete.mockResolvedValue({ success: true });

    expect(await updateCommentStatus('c1', 'approved')).toEqual({ ok: true, comment });
    expect(await deleteComment('c1')).toBe(true);
  });
});
