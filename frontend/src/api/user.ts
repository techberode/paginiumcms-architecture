// frontend/src/api/user.ts
// === Current user profile (alias over auth API) ===
import { authApi } from './auth';
import type { User } from './types';

export const userApi = {
  getCurrentUser: (): Promise<User | null> => authApi.getCurrentUser(),
  changePassword: (oldPassword: string, newPassword: string): Promise<boolean> =>
    authApi.changePassword(oldPassword, newPassword),
};
