// frontend/src/api/users.ts
// === Users API (Iterácia 5, admin) ===
import apiClient, { ApiResponse } from './client';
import { User } from './types';

export type UserRole = 'USER' | 'EDITOR' | 'ADMIN' | 'SUPER_ADMIN';

export interface CreateUserPayload {
  email: string;
  username: string;
  name: string;
  role: UserRole;
  password: string;
  passwordConfirm?: string;
  active?: boolean;
  twoFactorEnabled?: boolean;
  bio?: string;
}

export interface UpdateUserPayload {
  email?: string;
  username?: string;
  name?: string;
  role?: UserRole;
  password?: string;
  passwordConfirm?: string;
  active?: boolean;
  twoFactorEnabled?: boolean;
  bio?: string;
}

export interface UsersListResponse {
  users: User[];
  meta?: {
    require_two_factor_staff?: boolean;
    actor_is_super_admin?: boolean;
  };
}

export interface UserDetailResponse {
  user: User;
  meta?: {
    two_factor_enforced?: boolean;
    require_two_factor_staff?: boolean;
    actor_is_super_admin?: boolean;
  };
}

export async function listUsers(): Promise<UsersListResponse> {
  const res = await apiClient.get<UsersListResponse>('/api/admin/users');
  if (res.success && res.data) {
    return res.data;
  }
  return { users: [] };
}

export async function getUser(id: string): Promise<UserDetailResponse | null> {
  const res = await apiClient.get<UserDetailResponse>(`/api/admin/users/${encodeURIComponent(id)}`);
  return res.success && res.data ? res.data : null;
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

export async function uploadUserAvatar(id: string, file: File): Promise<ApiResponse<{ user: User }>> {
  const form = new FormData();
  form.append('avatar', file);

  // Let the browser set multipart boundary — manual Content-Type breaks PHP upload parsing.
  return apiClient.post<{ user: User }>(`/api/admin/users/${encodeURIComponent(id)}/avatar`, form);
}

export async function removeUserAvatar(id: string): Promise<ApiResponse<{ user: User }>> {
  return apiClient.delete<{ user: User }>(`/api/admin/users/${encodeURIComponent(id)}/avatar`);
}

export const USER_ROLES: UserRole[] = ['USER', 'EDITOR', 'ADMIN', 'SUPER_ADMIN'];

export const USER_ROLE_LABELS: Record<UserRole, string> = {
  USER: 'USER',
  EDITOR: 'EDITOR',
  ADMIN: 'ADMIN',
  SUPER_ADMIN: 'SUPER_ADMIN',
};

export function isStaffRole(role: UserRole): boolean {
  return role === 'EDITOR' || role === 'ADMIN' || role === 'SUPER_ADMIN';
}

export function deriveUsername(email: string): string {
  const local = email.split('@')[0] ?? 'user';
  return local.toLowerCase().replace(/[^a-z0-9_-]/g, '') || 'user';
}

export interface GdprExportPayload {
  exportedAt: string;
  schemaVersion: number;
  subjectUserId: string;
  profile: User;
  comments: Array<Record<string, unknown>>;
  newsletter: Record<string, unknown> | null;
  contactMessages: Array<Record<string, unknown>>;
  limits: { note: string };
}

export interface GdprAnonymizeResult {
  userId: string;
  pseudonym: string;
  commentsUpdated: number;
  contactMessagesUpdated: number;
  newsletterUpdated: boolean;
}

export async function exportUserGdprZip(userId: string): Promise<{ ok: true; blob: Blob } | { ok: false; error: string }> {
  try {
    const response = await fetch(`/api/admin/users/${encodeURIComponent(userId)}/gdpr/export?format=zip`, {
      credentials: 'include',
    });
    if (!response.ok) {
      return { ok: false, error: `Export failed (${response.status})` };
    }
    const blob = await response.blob();
    return { ok: true, blob };
  } catch {
    return { ok: false, error: 'Export failed.' };
  }
}

export async function anonymizeUserGdpr(userId: string): Promise<ApiResponse<{ result: GdprAnonymizeResult }>> {
  return apiClient.post<{ result: GdprAnonymizeResult }>(
    `/api/admin/users/${encodeURIComponent(userId)}/gdpr/anonymize`,
    { confirm: true }
  );
}
