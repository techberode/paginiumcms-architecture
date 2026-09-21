import apiClient, { type ApiResponse } from './client';

export interface TeamChatRoom {
  id: string;
  name: string;
  type?: string;
  shared?: boolean;
  canManageHistory?: boolean;
}

export interface TeamChatFile {
  id: string;
  name: string;
  size: number;
  mime: string;
}

export interface TeamChatInboxItem {
  teamId: string;
  teamName: string;
  teamType?: string;
  shared?: boolean;
  unread: number;
  preview: string;
  lastMessageAt: number;
}

export interface TeamChatInbox {
  count: number;
  items: TeamChatInboxItem[];
}

export interface TeamChatMessage {
  id: string;
  teamId: string;
  authorUserId: string;
  authorName: string;
  kind: 'text' | 'code' | 'file' | string;
  body: string;
  language?: string;
  file?: TeamChatFile | null;
  createdAt: number;
}

export const teamChatApi = {
  inbox: async (): Promise<TeamChatInbox> => {
    const response = await apiClient.get<TeamChatInbox>('/api/team-chat/inbox');
    if (response.success && response.data) {
      return {
        count: response.data.count ?? 0,
        items: response.data.items ?? [],
      };
    }

    return { count: 0, items: [] };
  },

  rooms: async (): Promise<TeamChatRoom[]> => {
    const response = await apiClient.get<{ rooms?: TeamChatRoom[] }>('/api/team-chat');
    return response.success ? (response.data?.rooms ?? []) : [];
  },

  search: async (teamId: string, query: string): Promise<TeamChatMessage[]> => {
    const q = encodeURIComponent(query.trim());
    const response = await apiClient.get<{ messages?: TeamChatMessage[] }>(
      `/api/team-chat/${encodeURIComponent(teamId)}/search?q=${q}`
    );
    return response.success ? (response.data?.messages ?? []) : [];
  },

  exportUrl: (teamId: string): string =>
    `/api/team-chat/${encodeURIComponent(teamId)}/export`,

  importArchive: async (teamId: string, archive: unknown): Promise<ApiResponse<{ imported: number }>> => {
    return apiClient.post<{ imported: number }>(
      `/api/team-chat/${encodeURIComponent(teamId)}/import`,
      archive
    );
  },

  clearHistory: async (teamId: string): Promise<ApiResponse<{ deleted: number }>> => {
    return apiClient.post<{ deleted: number }>(
      `/api/team-chat/${encodeURIComponent(teamId)}/clear-history`,
      {}
    );
  },

  messages: async (teamId: string): Promise<TeamChatMessage[]> => {
    const response = await apiClient.get<{ messages?: TeamChatMessage[] }>(
      `/api/team-chat/${encodeURIComponent(teamId)}`
    );
    return response.success ? (response.data?.messages ?? []) : [];
  },

  post: async (
    teamId: string,
    payload: { kind: 'text' | 'code'; body: string; language?: string }
  ): Promise<ApiResponse<{ message: TeamChatMessage }>> => {
    return apiClient.post<{ message: TeamChatMessage }>(
      `/api/team-chat/${encodeURIComponent(teamId)}`,
      payload
    );
  },

  upload: async (teamId: string, file: File): Promise<ApiResponse<{ message: TeamChatMessage }>> => {
    const form = new FormData();
    form.append('file', file);
    return apiClient.post<{ message: TeamChatMessage }>(
      `/api/team-chat/${encodeURIComponent(teamId)}/files`,
      form,
      { timeout: 120_000 }
    );
  },

  downloadUrl: (teamId: string, fileId: string): string =>
    `/api/team-chat/${encodeURIComponent(teamId)}/files/${encodeURIComponent(fileId)}`,
};
