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
  listLimit?: number;
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
  total?: number;
  unseen?: number;
}

export type MailSignatureTemplateId =
  | 'minimal'
  | 'classic'
  | 'card'
  | 'compact'
  | 'brand'
  | 'support';

export interface MailSignatureFields {
  displayName: string;
  jobTitle: string;
  phone: string;
  contactEmail: string;
  bio: string;
  companyName: string;
  website: string;
  avatarUrl: string;
}

export interface MailSignaturePrefs {
  enabled: boolean;
  templateId: MailSignatureTemplateId;
  overrides: Partial<MailSignatureFields>;
}

export interface MailSignatureState {
  mailbox: string;
  templates: Array<{ id: MailSignatureTemplateId }>;
  prefs: MailSignaturePrefs;
  fields: MailSignatureFields;
  previewHtml: string;
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
  remoteImagesBlocked?: boolean;
  seen?: boolean;
  flagged?: boolean;
  originFolder?: string;
  /** Stored only in CMS mail-client state (not on IMAP). */
  localOnly?: boolean;
  to?: string;
  cc?: string;
  replyTo?: string;
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

  message: async (folder: string, uid: number, allowRemoteImages = false): Promise<MailMessage | null> => {
    const remote = allowRemoteImages ? '&remoteImages=1' : '';
    const response = await apiClient.get<{ message: MailMessage }>(
      `/api/admin/mail/messages/${uid}?folder=${encodeURIComponent(folder)}${remote}`
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

  emptyLocalTrash: async (): Promise<ApiResponse<{ removed: number }>> => {
    return apiClient.post<{ removed: number }>('/api/admin/mail/local-trash/empty', {});
  },

  moveSpam: async (folder: string, uid: number): Promise<ApiResponse<{ moved: boolean }>> => {
    return apiClient.post<{ moved: boolean }>(`/api/admin/mail/messages/${uid}/spam`, { folder });
  },

  blockSender: async (
    folder: string,
    uid: number,
    from: string
  ): Promise<ApiResponse<{ blocked: string; moved: boolean }>> => {
    return apiClient.post<{ blocked: string; moved: boolean }>(`/api/admin/mail/messages/${uid}/block-sender`, {
      folder,
      from,
    });
  },

  autocleanSpam: async (): Promise<ApiResponse<{ purged: number }>> => {
    return apiClient.post<{ purged: number }>('/api/admin/mail/spam/autoclean', {});
  },

  blockedSenders: async (): Promise<{ mailbox: string; blocked: string[] }> => {
    const response = await apiClient.get<{ mailbox: string; blocked: string[] }>('/api/admin/mail/blocked-senders');
    if (response.success && response.data) {
      return { mailbox: response.data.mailbox, blocked: response.data.blocked ?? [] };
    }

    return { mailbox: '', blocked: [] };
  },

  unblockSender: async (email: string): Promise<ApiResponse<{ unblocked: string; removed: boolean }>> => {
    return apiClient.delete<{ unblocked: string; removed: boolean }>('/api/admin/mail/blocked-senders', {
      data: { email },
    });
  },

  signature: async (): Promise<MailSignatureState | null> => {
    const response = await apiClient.get<MailSignatureState>('/api/admin/mail/signature');
    return response.success && response.data ? response.data : null;
  },

  saveSignature: async (payload: {
    enabled?: boolean;
    templateId?: MailSignatureTemplateId;
    overrides?: Partial<MailSignatureFields>;
  }): Promise<ApiResponse<MailSignatureState>> => {
    return apiClient.put<MailSignatureState>('/api/admin/mail/signature', payload);
  },

  importSignatureProfile: async (): Promise<ApiResponse<MailSignatureState>> => {
    return apiClient.post<MailSignatureState>('/api/admin/mail/signature/import-profile', {});
  },

  send: async (payload: {
    to: string;
    subject: string;
    body: string;
  }): Promise<ApiResponse<{ sent: boolean; localUid: number }>> => {
    return apiClient.post<{ sent: boolean; localUid: number }>('/api/admin/mail/send', payload);
  },

  saveDraft: async (payload: {
    to: string;
    subject: string;
    body: string;
    uid?: number;
  }): Promise<ApiResponse<{ uid: number }>> => {
    return apiClient.put<{ uid: number }>('/api/admin/mail/drafts', payload);
  },

  deleteLocalMessage: async (folder: string, uid: number): Promise<ApiResponse<{ deleted: boolean }>> => {
    return apiClient.delete<{ deleted: boolean }>(
      `/api/admin/mail/messages/${uid}?folder=${encodeURIComponent(folder)}`
    );
  },
};
