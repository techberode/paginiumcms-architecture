// frontend/src/api/messages.ts
import apiClient from './client';

export interface ContactMessage {
  id: string;
  path: string;
  name: string;
  email: string;
  subject: string;
  message: string;
  createdAt: string;
  isRead: boolean;
  ip?: string;
}

export interface MessagesListResponse {
  items: ContactMessage[];
  count: number;
}

export async function listMessages(): Promise<ContactMessage[]> {
  const res = await apiClient.get<MessagesListResponse>('/api/admin/messages');
  return res.success && res.data?.items ? res.data.items : [];
}

export async function markMessageRead(id: string, isRead = true): Promise<boolean> {
  const res = await apiClient.patch(`/api/admin/messages/${encodeURIComponent(id)}`, { isRead });
  return res.success;
}

export async function deleteMessage(id: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/admin/messages/${encodeURIComponent(id)}`);
  return res.success;
}
