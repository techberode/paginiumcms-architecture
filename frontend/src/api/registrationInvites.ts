import apiClient, { type ApiResponse } from './client';

export interface RegistrationInvite {
  id: string;
  email: string;
  teamId: string;
  source: 'admin' | 'contact' | string;
  createdBy: string;
  createdAt: number;
  expiresAt: number;
  usedAt: number;
  userId: string;
}

export interface IssuedRegistrationInvite {
  invite: RegistrationInvite;
  token: string;
  url: string;
  mailed: boolean;
}

export interface PublicRegistrationInvite {
  email: string;
  expiresAt: number;
  teamId: string;
}

export const registrationInvitesApi = {
  list: async (): Promise<RegistrationInvite[]> => {
    const response = await apiClient.get<{ invites: RegistrationInvite[] }>('/api/admin/registration-invites');
    return response.success && response.data ? response.data.invites ?? [] : [];
  },

  create: async (payload: {
    email: string;
    teamId?: string;
    sendMail?: boolean;
    source?: 'admin' | 'contact';
  }): Promise<ApiResponse<IssuedRegistrationInvite>> => {
    return apiClient.post<IssuedRegistrationInvite>('/api/admin/registration-invites', payload);
  },

  revoke: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `/api/admin/registration-invites/${encodeURIComponent(id)}`
    );
    return response.success;
  },

  peek: async (token: string): Promise<PublicRegistrationInvite | null> => {
    const response = await apiClient.get<PublicRegistrationInvite>(
      `/api/auth/register-invite?token=${encodeURIComponent(token)}`
    );
    return response.success && response.data ? response.data : null;
  },
};
