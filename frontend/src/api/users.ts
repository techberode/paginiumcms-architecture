// frontend/src/api/users.ts
// === Users API (Iterácia 5, admin) ===
// CRUD správa používateľov – ZÁKON API↔FE: každý endpoint má typovaný klient.
import apiClient, { ApiResponse } from './client';
import { User } from './types';

export type UserRole = 'USER' | 'EDITOR' | 'ADMIN' | 'SUPER_ADMIN';

export interface CreateUserPayload {
  email: string;
  name: string;
  role: UserRole;
  password: string;
}

export interface UpdateUserPayload {
  email?: string;
  name?: string;
  role?: UserRole;
  password?: string;
}

export async function listUsers(): Promise<User[]> {
  const res = await apiClient.get<{ users: User[] }>('/api/admin/users');
  return res.success && res.data?.users ? res.data.users : [];
}

export async function getUser(id: string): Promise<User | null> {
  const res = await apiClient.get<{ user: User }>(`/api/admin/users/${encodeURIComponent(id)}`);
  return res.success && res.data?.user ? res.data.user : null;
}

export async function createUser(payload: CreateUserPayload): Promise<ApiResponse<{ user: User }>> {
  return apiClient.post<{ user: User }>('/api/admin/users', payload);
}

export async function updateUser(id: string, payload: UpdateUserPayload): Promise<ApiResponse<{ user: User }>> {
  return apiClient.put<{ user: User }>(`/api/admin/users/${encodeURIComponent(id)}`, payload);
}

export async function deleteUser(id: string): Promise<ApiResponse<unknown>> {
  return apiClient.delete(`/api/admin/users/${encodeURIComponent(id)}`);
}

export async function bulkDeleteUsers(ids: string[]): Promise<import('../types/bulk').BulkBatchResult | null> {
  const res = await apiClient.post<import('../types/bulk').BulkBatchResult>('/api/admin/users/bulk-delete', { ids });
  return res.success && res.data ? res.data : null;
}

export const USER_ROLES: UserRole[] = ['USER', 'EDITOR', 'ADMIN', 'SUPER_ADMIN'];
