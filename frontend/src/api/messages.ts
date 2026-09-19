// frontend/src/api/messages.ts
import apiClient from './client';
import type { BulkBatchResult } from '../types/bulk';
import type { MessagePriority } from '../constants/messageSubjects';

export interface MessageReply {
  id: string;
  authorType: 'visitor' | 'staff' | string;
  authorUserId?: string;
  authorName?: string;
  body: string;
  createdAt: string;
}

export interface MessageRoute {
  subject: string;
  enabled: boolean;
  teamIds: string[];
  userIds: string[];
}

export interface MessageRouting {
  schema?: string;
  enabled: boolean;
  routes: MessageRoute[];
}

export interface ContactMessage {
  id: string;
  path: string;
  name: string;
  email: string;
  phone?: string;
  subject: string;
  message: string;
  createdAt: string;
  isRead: boolean;
  isProcessed: boolean;
  isArchived: boolean;
  priority: MessagePriority | string;
  ip?: string;
  channel?: string;
  staffUserId?: string;
  assigneeUserIds?: string[];
  assigneeTeamIds?: string[];
  claimedBy?: string;
  claimedAt?: number;
  handleStatus?: string;
  notifyUserId?: string;
  thread?: MessageReply[];
  desk?: 'priority' | 'later' | string;
  mine?: boolean;
  canClaim?: boolean;
  canReply?: boolean;
  claimedByName?: string;
  registrationRequest?: boolean;
  mailed?: boolean;
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

export const messagesApi = {
  claim: async (id: string): Promise<ContactMessage | null> => {
    const res = await apiClient.post<ContactMessage>(`/api/admin/messages/${encodeURIComponent(id)}/claim`, {});
    return res.success && res.data ? res.data : null;
  },
  release: async (id: string): Promise<ContactMessage | null> => {
    const res = await apiClient.post<ContactMessage>(`/api/admin/messages/${encodeURIComponent(id)}/release`, {});
    return res.success && res.data ? res.data : null;
  },
  reply: async (id: string, body: string): Promise<ContactMessage | null> => {
    const res = await apiClient.post<ContactMessage>(`/api/admin/messages/${encodeURIComponent(id)}/replies`, { body });
    return res.success && res.data ? res.data : null;
  },
  routing: async (): Promise<MessageRouting> => {
    const res = await apiClient.get<MessageRouting>('/api/admin/messages/routing');
    return res.success && res.data
      ? { enabled: Boolean(res.data.enabled), routes: Array.isArray(res.data.routes) ? res.data.routes : [] }
      : { enabled: false, routes: [] };
  },
  saveRouting: async (payload: MessageRouting): Promise<boolean> => {
    const res = await apiClient.put('/api/admin/messages/routing', payload);
    return Boolean(res.success);
  },
};
