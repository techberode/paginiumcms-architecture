// frontend/src/api/messages.ts
import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';
import type { MessagePriority } from '../constants/messageSubjects';

export interface ContactMessage {
  id: string;
  path: string;
  name: string;
  email: string;
  subject: string;
  message: string;
  createdAt: string;
  isRead: boolean;
  isProcessed: boolean;
  isArchived: boolean;
  priority: MessagePriority | string;
  ip?: string;
}

export interface MessagesListResponse {
  items: ContactMessage[];
  count: number;
}

export type MessageBulkAction = 'read' | 'processed' | 'archive' | 'delete';

export async function listMessages(): Promise<ContactMessage[]> {
  const res = await apiClient.get<MessagesListResponse>('/api/admin/messages');
  return res.success && res.data?.items ? res.data.items : [];
}

export async function updateMessage(
  id: string,
  patch: Partial<Pick<ContactMessage, 'isRead' | 'isProcessed' | 'isArchived' | 'priority'>>
): Promise<ContactMessage | null> {
  const res = await apiClient.patch<ContactMessage>(`/api/admin/messages/${encodeURIComponent(id)}`, patch);
  return res.success && res.data ? res.data : null;
}

export async function markMessageRead(id: string, isRead = true): Promise<boolean> {
  const updated = await updateMessage(id, { isRead });
  return updated !== null;
}

export async function deleteMessage(id: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/admin/messages/${encodeURIComponent(id)}`);
  return res.success;
}

export async function bulkMessageAction(
  ids: string[],
  action: MessageBulkAction
): Promise<BulkBatchResult | null> {
  const res = await apiClient.post<BulkBatchResult>('/api/admin/messages/bulk', { ids, action });
  return res.success && res.data ? res.data : null;
}
