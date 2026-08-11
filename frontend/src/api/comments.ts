// frontend/src/api/comments.ts
import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';

export type CommentStatus = 'pending' | 'approved' | 'rejected' | 'quarantine';

export interface Comment {
  id: string;
  articleSlug: string;
  author: string;
  email?: string;
  content: string;
  status: CommentStatus;
  createdAt: string;
  approvedAt?: string | null;
  isRead?: boolean;
  isArchived?: boolean;
}

export interface AdminCommentsResponse {
  items: Comment[];
  count: number;
}

export async function listPublicComments(articleSlug: string): Promise<Comment[]> {
  const res = await apiClient.get<Comment[]>(`/api/comments?articleSlug=${encodeURIComponent(articleSlug)}`);
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export async function submitComment(payload: {
  articleSlug: string;
  author: string;
  email?: string;
  content: string;
  _hp?: string;
}): Promise<{ ok: true; comment: Comment } | { ok: false; error: string }> {
  const res = await apiClient.post<Comment>('/api/comments', payload);
  if (res.success && res.data) {
    return { ok: true, comment: res.data };
  }
  return { ok: false, error: res.error ?? 'Failed to submit comment.' };
}

export async function listAdminComments(filters?: {
  articleSlug?: string;
  status?: CommentStatus;
}): Promise<Comment[]> {
  const params = new URLSearchParams();
  if (filters?.articleSlug) params.set('articleSlug', filters.articleSlug);
  if (filters?.status) params.set('status', filters.status);
  const qs = params.toString();
  const url = qs ? `/api/admin/comments?${qs}` : '/api/admin/comments';
  const res = await apiClient.get<AdminCommentsResponse>(url);
  return res.success && res.data?.items ? res.data.items : [];
}

export async function updateCommentStatus(
  id: string,
  status: CommentStatus
): Promise<
  | { ok: true; comment: Comment }
  | { ok: true; requiresOtp: true; challengeId: string; debugCode?: string }
  | { ok: false; error?: string }
> {
  const res = await apiClient.put<Comment>(`/api/admin/comments/${encodeURIComponent(id)}`, { status });

  const body = res as unknown as {
    success?: boolean;
    requires_otp?: boolean;
    challenge_id?: unknown;
    debug_code?: unknown;
  };

  if (res.success && body.requires_otp === true) {
    return {
      ok: true,
      requiresOtp: true,
      challengeId: typeof body.challenge_id === 'string' ? body.challenge_id : '',
      debugCode: typeof body.debug_code === 'string' ? body.debug_code : undefined,
    };
  }
  if (res.success && res.data) {
    return { ok: true, comment: res.data as Comment };
  }
  return { ok: false, error: res.error || 'Update failed' };
}

export async function deleteComment(id: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/admin/comments/${encodeURIComponent(id)}`);
  return res.success;
}

export async function bulkUpdateCommentStatus(
  ids: string[],
  status: CommentStatus
): Promise<BulkBatchResult | null> {
  const res = await apiClient.post<BulkBatchResult>('/api/admin/comments/bulk-status', { ids, status });
  return res.success && res.data ? res.data : null;
}

export async function bulkDeleteComments(ids: string[]): Promise<BulkBatchResult | null> {
  const res = await apiClient.post<BulkBatchResult>('/api/admin/comments/bulk-delete', { ids });
  return res.success && res.data ? res.data : null;
}

export type CommentBulkWorkflowAction = 'read' | 'processed' | 'archive';

export async function bulkCommentWorkflow(
  ids: string[],
  action: CommentBulkWorkflowAction
): Promise<BulkBatchResult | null> {
  const res = await apiClient.post<BulkBatchResult>('/api/admin/comments/bulk-workflow', { ids, action });
  return res.success && res.data ? res.data : null;
}

export async function updateCommentFlags(
  id: string,
  patch: { isRead?: boolean; isArchived?: boolean }
): Promise<Comment | null> {
  const res = await apiClient.put<Comment>(`/api/admin/comments/${encodeURIComponent(id)}`, patch);
  return res.success && res.data ? (res.data as Comment) : null;
}
