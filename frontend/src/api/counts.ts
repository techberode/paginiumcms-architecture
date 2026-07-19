// frontend/src/api/counts.ts
import apiClient from './client';

export interface AdminCounts {
  pages: number;
  articles: number;
  media: number;
  backups: number;
  comments?: number;
  messages?: number;
  trash?: number;
  users?: number;
}

export async function getAdminCounts(): Promise<AdminCounts | null> {
  const res = await apiClient.get<AdminCounts>('/api/admin/counts');
  return res.success && res.data ? res.data : null;
}
