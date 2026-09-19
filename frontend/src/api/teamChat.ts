import apiClient, { type ApiResponse } from './client';

export interface TeamChatRoom {
  id: string;
  name: string;
}

export interface TeamChatFile {
  id: string;
  name: string;
  size: number;
  mime: string;
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
  rooms: async (): Promise<TeamChatRoom[]> => {
    const response = await apiClient.get<{ rooms?: TeamChatRoom[] }>('/api/team-chat');
    return response.success ? (response.data?.rooms ?? []) : [];
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
