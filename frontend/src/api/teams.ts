import apiClient, { type ApiResponse } from './client';

export const TEAM_TYPES = ['editorial', 'support', 'ops', 'external', 'custom'] as const;

export type TeamType = (typeof TEAM_TYPES)[number];

export const TEAM_COLOR_SWATCHES = [
  '#2563eb',
  '#0f766e',
  '#7c3aed',
  '#b45309',
  '#be123c',
  '#334155',
  '#0369a1',
  '#15803d',
] as const;

export interface TeamMember {
  id: string;
  name: string;
  username: string;
  email: string;
  active: boolean;
  avatarUrl?: string | null;
}

export interface Team {
  id: string;
  name: string;
  type: TeamType;
  memberUserIds: string[];
  members: TeamMember[];
  color?: string;
  chatEnabled?: boolean;
  replyMailEnabled?: boolean;
  replyMail?: string;
  createdAt: number;
  updatedAt: number;
}

export interface TeamsIndex {
  teams: Team[];
  types: TeamType[];
  users: TeamMember[];
}

export interface TeamPayload {
  name: string;
  type: TeamType;
  memberUserIds: string[];
  color?: string;
  chatEnabled?: boolean;
  replyMailEnabled?: boolean;
  replyMail?: string;
}

function emptyIndex(): TeamsIndex {
  return { teams: [], types: [...TEAM_TYPES], users: [] };
}

export const teamsApi = {
  list: async (type?: TeamType): Promise<TeamsIndex> => {
    const query = type ? `?type=${encodeURIComponent(type)}` : '';
    const response = await apiClient.get<TeamsIndex>(`/api/admin/teams${query}`);
    if (response.success && response.data) {
      return {
        teams: response.data.teams ?? [],
        types: response.data.types ?? [...TEAM_TYPES],
        users: response.data.users ?? [],
      };
    }

    return emptyIndex();
  },

  create: async (payload: TeamPayload): Promise<ApiResponse<{ team: Team }>> => {
    return apiClient.post<{ team: Team }>('/api/admin/teams', payload);
  },

  update: async (id: string, payload: Partial<TeamPayload>): Promise<ApiResponse<{ team: Team }>> => {
    return apiClient.put<{ team: Team }>(`/api/admin/teams/${encodeURIComponent(id)}`, payload);
  },

  remove: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(`/api/admin/teams/${encodeURIComponent(id)}`);
    return response.success;
  },
};
