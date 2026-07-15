// frontend/src/api/comments.ts
import apiClient from './client';

export type CommentStatus = 'pending' | 'approved' | 'rejected';

export interface Comment {
  id: string;
  articleSlug: string;
  author: string;
  email?: string;
  content: string;
  status: CommentStatus;
  createdAt: string;
  approvedAt?: string | null;
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
): Promise<boolean> {
  const res = await apiClient.put(`/api/admin/comments/${encodeURIComponent(id)}`, { status });
  return res.success;
}

export async function deleteComment(id: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/admin/comments/${encodeURIComponent(id)}`);
  return res.success;
}
