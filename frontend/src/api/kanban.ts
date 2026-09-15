import apiClient, { type ApiResponse } from './client';

export interface KanbanColumn {
  id: string;
  label: string;
  color: string;
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
  createdAt: number;
  updatedAt: number;
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
  };
}

export const supportKanbanApi = {
  list: async (): Promise<KanbanIndex> => {
    const response = await apiClient.get<KanbanIndex>('/api/admin/support-kanban');
    if (response.success && response.data) {
      return {
        board: response.data.board ?? emptyIndex().board,
        tickets: response.data.tickets ?? [],
        agents: response.data.agents ?? [],
      };
    }

    return emptyIndex();
  },

  saveBoard: async (payload: { columns: KanbanColumn[]; labels: KanbanLabel[] }): Promise<ApiResponse<{ board: KanbanBoard }>> => {
    return apiClient.put<{ board: KanbanBoard }>('/api/admin/support-kanban/board', payload);
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

  removeTicket: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `/api/admin/support-kanban/tickets/${encodeURIComponent(id)}`
    );
    return response.success;
  },
};
