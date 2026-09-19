import apiClient, { type ApiResponse } from './client';

export interface PublicRegistrationOption {
  id: string;
  label: string;
}

export interface RegistrationOption {
  id: string;
  label: string;
  roleId: string;
  enabled: boolean;
  requireAdminApproval: boolean;
  assignTeamId: string;
  welcomeMailEnabled: boolean;
  welcomeMailSubject: string;
  welcomeMailBody: string;
}

export interface RegistrationRoleChoice {
  id: string;
  name: string;
}

export interface RegistrationOptionsIndex {
  options: RegistrationOption[];
  roles: RegistrationRoleChoice[];
}

export const registrationOptionsApi = {
  publicList: async (): Promise<PublicRegistrationOption[]> => {
    const response = await apiClient.get<{ options?: PublicRegistrationOption[] }>('/api/auth/register-options');
    return response.success ? (response.data?.options ?? []) : [];
  },

  list: async (): Promise<RegistrationOptionsIndex> => {
    const response = await apiClient.get<RegistrationOptionsIndex>('/api/admin/registration-options');
    if (response.success && response.data) {
      return {
        options: response.data.options ?? [],
        roles: response.data.roles ?? [],
      };
    }
    return { options: [], roles: [] };
  },

  save: async (options: RegistrationOption[]): Promise<ApiResponse<{ options: RegistrationOption[] }>> => {
    return apiClient.put<{ options: RegistrationOption[] }>('/api/admin/registration-options', { options });
  },
};
