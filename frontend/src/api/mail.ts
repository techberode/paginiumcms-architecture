import apiClient, { type ApiResponse } from './client';

export const MAIL_LOCAL_TRASH = '__local_trash';

export interface MailStatus {
  enabled: boolean;
  configured: boolean;
  siteHost: string;
  mailbox: string;
  mailboxAllowed: boolean;
  hasPassword: boolean;
  canReadAll: boolean;
  smtpEnabled: boolean;
  canSend: boolean;
  accounts: MailAccount[];
}

export interface MailAccount {
  mailbox: string;
  primary: boolean;
  hasPassword: boolean;
}

export interface MailFolder {
  name: string;
  spam: boolean;
  virtual?: boolean;
}

export interface MailMessage {
  uid: number;
  subject: string;
  from: string;
  date: string;
  flags: string[];
  tags: string[];
  snippet: string;
  body?: string;
  html?: string;
  seen?: boolean;
  flagged?: boolean;
  originFolder?: string;
}

export const mailApi = {
  status: async (): Promise<MailStatus | null> => {
    const response = await apiClient.get<MailStatus>('/api/admin/mail');
    if (response.success && response.data) {
      return response.data;
    }

    return null;
  },

  savePassword: async (password: string): Promise<ApiResponse<{ saved: boolean }>> => {
    return apiClient.put<{ saved: boolean }>('/api/admin/mail/password', { password });
  },

  addAccount: async (mailbox: string, password: string): Promise<ApiResponse<{ added: boolean }>> => {
    return apiClient.post<{ added: boolean }>('/api/admin/mail/accounts', { mailbox, password });
  },

  removeAccount: async (mailbox: string): Promise<ApiResponse<{ removed: boolean }>> => {
    return apiClient.delete<{ removed: boolean }>('/api/admin/mail/accounts', { data: { mailbox } });
  },

  selectAccount: async (mailbox: string): Promise<ApiResponse<{ mailbox: string }>> => {
    return apiClient.put<{ mailbox: string }>('/api/admin/mail/active', { mailbox });
  },

  folders: async (): Promise<MailFolder[]> => {
    const response = await apiClient.get<{ folders: MailFolder[] }>('/api/admin/mail/folders');
    if (response.success && response.data) {
      return response.data.folders ?? [];
    }

    return [];
  },

  createFolder: async (name: string): Promise<ApiResponse<{ created: boolean }>> => {
    return apiClient.post<{ created: boolean }>('/api/admin/mail/folders', { name });
  },

  deleteFolder: async (name: string): Promise<ApiResponse<{ deleted: boolean }>> => {
    return apiClient.delete<{ deleted: boolean }>('/api/admin/mail/folders', { data: { name } });
  },

  messages: async (folder: string): Promise<MailMessage[]> => {
    const response = await apiClient.get<{ messages: MailMessage[] }>(
      `/api/admin/mail/messages?folder=${encodeURIComponent(folder)}`
    );
    if (response.success && response.data) {
      return response.data.messages ?? [];
    }

    return [];
  },

  message: async (folder: string, uid: number): Promise<MailMessage | null> => {
    const response = await apiClient.get<{ message: MailMessage }>(
      `/api/admin/mail/messages/${uid}?folder=${encodeURIComponent(folder)}`
    );
    if (response.success && response.data) {
      return response.data.message;
    }

    return null;
  },

  setTags: async (folder: string, uid: number, tags: string[]): Promise<ApiResponse<{ tagged: boolean }>> => {
    return apiClient.put<{ tagged: boolean }>(`/api/admin/mail/messages/${uid}/tags`, { folder, tags });
  },

  changeFlags: async (
    folder: string,
    uid: number,
    add: string[],
    remove: string[]
  ): Promise<ApiResponse<{ updated: boolean }>> => {
    return apiClient.patch<{ updated: boolean }>(`/api/admin/mail/messages/${uid}/flags`, { folder, add, remove });
  },

  hide: async (folder: string, uid: number, meta: Pick<MailMessage, 'subject' | 'from' | 'date'>): Promise<ApiResponse<{ hidden: boolean }>> => {
    return apiClient.post<{ hidden: boolean }>(`/api/admin/mail/messages/${uid}/hide`, { folder, ...meta });
  },

  unhide: async (folder: string, uid: number): Promise<ApiResponse<{ restored: boolean }>> => {
    return apiClient.post<{ restored: boolean }>(`/api/admin/mail/messages/${uid}/unhide`, { folder });
  },

  moveSpam: async (folder: string, uid: number): Promise<ApiResponse<{ moved: boolean }>> => {
    return apiClient.post<{ moved: boolean }>(`/api/admin/mail/messages/${uid}/spam`, { folder });
  },

  send: async (payload: { to: string; subject: string; body: string }): Promise<ApiResponse<{ sent: boolean }>> => {
    return apiClient.post<{ sent: boolean }>('/api/admin/mail/send', payload);
  },
};
