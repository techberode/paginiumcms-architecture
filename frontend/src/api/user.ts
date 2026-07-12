// frontend/src/api/user.ts
import apiClient from './client';
import { User } from './types';

export const userApi = {
  // Získanie všetkých používateľov
  getAll: async (): Promise<User[]> => {
    const response = await apiClient.get<User[]>('/api/admin/users');
    return response.data || [];
  },

  // Získanie používateľa podľa ID
  getById: async (id: string): Promise<User> => {
    const response = await apiClient.get<User>(`/api/admin/users/${id}`);
    return response.data as User;
  },

  // Vytvorenie používateľa
  create: async (data: {
    email: string;
    password: string;
    name: string;
    roles?: string[];
  }): Promise<User> => {
    const response = await apiClient.post<User>('/api/admin/users', data);
    return response.data as User;
  },

  // Aktualizácia používateľa
  update: async (id: string, data: Partial<User>): Promise<User> => {
    const response = await apiClient.put<User>(`/api/admin/users/${id}`, data);
    return response.data as User;
  },

  // Vymazanie používateľa
  delete: async (id: string): Promise<{ success: boolean }> => {
    const response = await apiClient.delete(`/api/admin/users/${id}`);
    return response.data as { success: boolean };
  },

  // Zmena rolí
  updateRoles: async (id: string, roles: string[]): Promise<User> => {
    const response = await apiClient.patch<User>(`/api/admin/users/${id}/roles`, { roles });
    return response.data as User;
  },
};
