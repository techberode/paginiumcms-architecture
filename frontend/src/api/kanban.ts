import apiClient, { type ApiResponse } from './client';

export interface KanbanColumn {
  id: string;
  label: string;
  color: string;
  wipLimit?: number;
}

export interface KanbanLabel {
  id: string;
  name: string;
  color: string;
}

export interface KanbanBoard {
  schema: string;
  columns: KanbanColumn[];
  labels: KanbanLabel[];
  statsEnabled?: boolean;
}

export interface KanbanStats {
  openCount: number;
  completedCount: number;
  avgSeconds: number | null;
  medianSeconds: number | null;
}

export interface InternalNote {
  id: string;
  body: string;
  authorUserId: string;
  createdAt: number;
}

export interface CannedReply {
  id: string;
  title: string;
  body: string;
}

export interface SupportTicket {
  schema: string;
  id: string;
  subject: string;
  body: string;
  columnId: string;
  order: number;
  assigneeUserId: string | null;
  requesterName: string;
  requesterEmail: string;
  messageId: string | null;
  labelIds: string[];
  dueAt: number | null;
  internalNotes: InternalNote[];
  createdAt: number;
  updatedAt: number;
  completedAt?: number | null;
}

export interface KanbanAgent {
  id: string;
  name: string;
  email: string;
}

export interface KanbanIndex {
  board: KanbanBoard;
  tickets: SupportTicket[];
  agents: KanbanAgent[];
  cannedReplies: CannedReply[];
  stats?: KanbanStats;
}

export interface KanbanTeamContext {
  id: string;
  name: string;
  color: string;
  canManageBoard: boolean;
}

export interface KanbanTeamSummary {
  id: string;
  name: string;
  color: string;
  type: string;
  canManageBoard: boolean;
}

export interface TeamKanbanIndex extends KanbanIndex {
  team: KanbanTeamContext;
}

export interface TicketPayload {
  subject: string;
  body?: string;
  columnId?: string;
  order?: number;
  assigneeUserId?: string | null;
  requesterName?: string;
  requesterEmail?: string;
  messageId?: string | null;
  labelIds?: string[];
  dueAt?: number | null;
}

function emptyIndex(): KanbanIndex {
  return {
    board: { schema: 'support-board@1', columns: [], labels: [] },
    tickets: [],
    agents: [],
    cannedReplies: [],
  };
}

export type KanbanLoadResult =
  | { ok: true; data: KanbanIndex }
  | { ok: false; forbidden: boolean; error?: string };

export type TeamKanbanLoadResult =
  | { ok: true; data: TeamKanbanIndex; teams: KanbanTeamSummary[] }
  | { ok: false; forbidden: boolean; error?: string };

function normalizeIndex(raw: KanbanIndex): KanbanIndex {
  return {
    board: raw.board ?? emptyIndex().board,
    tickets: (raw.tickets ?? []).map((ticket) => ({
      ...ticket,
      internalNotes: ticket.internalNotes ?? [],
      labelIds: ticket.labelIds ?? [],
    })),
    agents: raw.agents ?? [],
    cannedReplies: raw.cannedReplies ?? [],
    stats: raw.stats,
  };
}

function teamBase(teamId: string): string {
  return `/api/admin/team-kanban/${encodeURIComponent(teamId)}`;
}

export const teamKanbanApi = {
  listTeams: async (): Promise<{ ok: true; teams: KanbanTeamSummary[] } | { ok: false; forbidden: boolean }> => {
    const response = await apiClient.get<{ teams: KanbanTeamSummary[] }>('/api/admin/team-kanban');
    if (response.success && response.data) {
      return { ok: true, teams: response.data.teams ?? [] };
    }

    return { ok: false, forbidden: response.status === 403 };
  },

  load: async (teamId: string): Promise<TeamKanbanLoadResult> => {
    const teamsResult = await teamKanbanApi.listTeams();
    if (!teamsResult.ok) {
      return { ok: false, forbidden: teamsResult.forbidden, error: undefined };
    }

    const response = await apiClient.get<TeamKanbanIndex>(teamBase(teamId));
    if (response.success && response.data?.team) {
      return {
        ok: true,
        teams: teamsResult.teams,
        data: {
          ...normalizeIndex(response.data),
          team: response.data.team,
        },
      };
    }

    return {
      ok: false,
      forbidden: response.status === 403,
      error: response.error ?? response.message,
    };
  },

  saveBoard: async (
    teamId: string,
    payload: { columns: KanbanColumn[]; labels: KanbanLabel[]; statsEnabled?: boolean }
  ): Promise<ApiResponse<{ board: KanbanBoard }>> => {
    return apiClient.put<{ board: KanbanBoard }>(`${teamBase(teamId)}/board`, payload);
  },

  saveCanned: async (
    teamId: string,
    replies: CannedReply[]
  ): Promise<ApiResponse<{ cannedReplies: CannedReply[] }>> => {
    return apiClient.put<{ cannedReplies: CannedReply[] }>(`${teamBase(teamId)}/canned`, { replies });
  },

  createTicket: async (teamId: string, payload: TicketPayload): Promise<ApiResponse<{ ticket: SupportTicket }>> => {
    return apiClient.post<{ ticket: SupportTicket }>(`${teamBase(teamId)}/tickets`, payload);
  },

  updateTicket: async (
    teamId: string,
    id: string,
    payload: Partial<TicketPayload>
  ): Promise<ApiResponse<{ ticket: SupportTicket }>> => {
    return apiClient.put<{ ticket: SupportTicket }>(`${teamBase(teamId)}/tickets/${encodeURIComponent(id)}`, payload);
  },

  addNote: async (
    teamId: string,
    id: string,
    body: string
  ): Promise<ApiResponse<{ ticket: SupportTicket }>> => {
    return apiClient.post<{ ticket: SupportTicket }>(`${teamBase(teamId)}/tickets/${encodeURIComponent(id)}/notes`, {
      body,
    });
  },

  removeTicket: async (teamId: string, id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `${teamBase(teamId)}/tickets/${encodeURIComponent(id)}`
    );
    return response.success;
  },
};

/** @deprecated Legacy global board; prefer {@link teamKanbanApi}. */
export const supportKanbanApi = {
  list: async (): Promise<KanbanLoadResult> => {
    const response = await apiClient.get<KanbanIndex>('/api/admin/support-kanban');
    if (response.success && response.data) {
      return { ok: true, data: normalizeIndex(response.data) };
    }

    return {
      ok: false,
      forbidden: response.status === 403,
      error: response.error ?? response.message,
    };
  },

  saveBoard: async (payload: {
    columns: KanbanColumn[];
    labels: KanbanLabel[];
    statsEnabled?: boolean;
  }): Promise<ApiResponse<{ board: KanbanBoard }>> => {
    return apiClient.put<{ board: KanbanBoard }>('/api/admin/support-kanban/board', payload);
  },

  saveCanned: async (replies: CannedReply[]): Promise<ApiResponse<{ cannedReplies: CannedReply[] }>> => {
    return apiClient.put<{ cannedReplies: CannedReply[] }>('/api/admin/support-kanban/canned', { replies });
  },

  createTicket: async (payload: TicketPayload): Promise<ApiResponse<{ ticket: SupportTicket }>> => {
    return apiClient.post<{ ticket: SupportTicket }>('/api/admin/support-kanban/tickets', payload);
  },

  updateTicket: async (
    id: string,
    payload: Partial<TicketPayload>
  ): Promise<ApiResponse<{ ticket: SupportTicket }>> => {
    return apiClient.put<{ ticket: SupportTicket }>(
      `/api/admin/support-kanban/tickets/${encodeURIComponent(id)}`,
      payload
    );
  },

  addNote: async (id: string, body: string): Promise<ApiResponse<{ ticket: SupportTicket }>> => {
    return apiClient.post<{ ticket: SupportTicket }>(
      `/api/admin/support-kanban/tickets/${encodeURIComponent(id)}/notes`,
      { body }
    );
  },

  removeTicket: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `/api/admin/support-kanban/tickets/${encodeURIComponent(id)}`
    );
    return response.success;
  },
};
