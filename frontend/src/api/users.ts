// frontend/src/api/users.ts
// === Users API (Iterácia 5, admin) ===
import apiClient, { ApiResponse } from './client';
import { User } from './types';
import { resolvePublicMediaUrl, uploadMedia } from './media';

export type UserRole = string;

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
    assignable_roles?: string[];
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

export function resolveUserAvatarUrl(url: string | null | undefined): string {
  const raw = url?.trim() ?? '';
  if (raw === '') {
    return '';
  }

  if (raw.startsWith('http://') || raw.startsWith('https://')) {
    return raw;
  }

  if (raw.startsWith('/storage/') || raw.startsWith('/api/media/file/')) {
    return resolvePublicMediaUrl(raw);
  }

  if (raw.startsWith('media/')) {
    return resolvePublicMediaUrl(`/storage/app/content/${raw}`);
  }

  return raw;
}

export async function assignUserAvatarFromUrl(
  id: string,
  url: string
): Promise<ApiResponse<{ user: User }>> {
  return apiClient.put<{ user: User }>(`/api/admin/users/${encodeURIComponent(id)}/avatar`, { url });
}

export async function uploadUserAvatar(id: string, file: File): Promise<ApiResponse<{ user: User }>> {
  const folder = `avatars/${id}`;
  const upload = await uploadMedia(file, file.name, folder);
  if (!upload.ok) {
    return { success: false, error: upload.error };
  }

  return assignUserAvatarFromUrl(id, resolvePublicMediaUrl(upload.media.url));
}

export async function removeUserAvatar(id: string): Promise<ApiResponse<{ user: User }>> {
  return apiClient.delete<{ user: User }>(`/api/admin/users/${encodeURIComponent(id)}/avatar`);
}

export const USER_ROLES: UserRole[] = ['USER', 'EDITOR', 'ADMIN', 'SUPER_ADMIN'];

export function isKnownUserRole(role: string): role is 'USER' | 'EDITOR' | 'ADMIN' | 'SUPER_ADMIN' {
  return USER_ROLES.includes(role);
}

export const USER_ROLE_LABELS: Record<string, string> = {
  USER: 'USER',
  EDITOR: 'EDITOR',
  ADMIN: 'ADMIN',
  SUPER_ADMIN: 'SUPER_ADMIN',
};

export function isStaffRole(role: UserRole): boolean {
  return role === 'EDITOR' || role === 'ADMIN' || role === 'SUPER_ADMIN';
}

export function isValidUserRole(role: string): boolean {
  return /^(SUPER_ADMIN|[A-Z][A-Z0-9_]{1,31})$/.test(role);
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
