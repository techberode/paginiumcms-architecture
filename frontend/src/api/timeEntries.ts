import apiClient, { type ApiResponse } from './client';

export const TIME_TARGETS = ['planItem', 'event', 'content'] as const;

export type TimeTarget = (typeof TIME_TARGETS)[number];

export interface TimeEntry {
  schema: string;
  id: string;
  userId: string;
  target: TimeTarget;
  planId: string | null;
  planItemId: string | null;
  eventId: string | null;
  contentKind?: 'page' | 'article' | null;
  contentSlug?: string | null;
  startedAt: number;
  endedAt: number | null;
  seconds: number;
  note: string;
  label: string;
  createdAt: number;
  updatedAt: number;
}

export interface TimeTargetEvent {
  id: string;
  title: string;
}

export interface TimeTargetPlanItem {
  id: string;
  title: string;
}

export interface TimeTargetPlan {
  id: string;
  title: string;
  items: TimeTargetPlanItem[];
}

export interface TimeSummary {
  todaySeconds: number;
  weekSeconds: number;
  teamTodaySeconds?: number;
  teamWeekSeconds?: number;
  byUserToday?: Record<string, number>;
}

export interface TimeEntriesIndex {
  entries: TimeEntry[];
  running: TimeEntry | null;
  summary: TimeSummary;
  canSeeTeam: boolean;
  targets: TimeTarget[];
}

export interface TimeStartPayload {
  target: TimeTarget;
  planId?: string;
  planItemId?: string;
  eventId?: string;
  contentKind?: 'page' | 'article';
  contentSlug?: string;
  note?: string;
}

function emptyIndex(): TimeEntriesIndex {
  return {
    entries: [],
    running: null,
    summary: { todaySeconds: 0, weekSeconds: 0 },
    canSeeTeam: false,
    targets: [...TIME_TARGETS],
  };
}

export const timeEntriesApi = {
  list: async (query?: { scope?: 'team'; planId?: string }): Promise<TimeEntriesIndex> => {
    const params = new URLSearchParams();
    if (query?.scope === 'team') {
      params.set('scope', 'team');
    }
    if (query?.planId) {
      params.set('planId', query.planId);
    }
    const suffix = params.toString() !== '' ? `?${params.toString()}` : '';
    const response = await apiClient.get<TimeEntriesIndex>(`/api/admin/time-entries${suffix}`);
    if (response.success && response.data) {
      return {
        entries: response.data.entries ?? [],
        running: response.data.running ?? null,
        summary: response.data.summary ?? { todaySeconds: 0, weekSeconds: 0 },
        canSeeTeam: response.data.canSeeTeam === true,
        targets: response.data.targets ?? [...TIME_TARGETS],
      };
    }

    return emptyIndex();
  },

  targets: async (): Promise<{
    events: TimeTargetEvent[];
    plans: TimeTargetPlan[];
    pages: Array<{ slug: string; title: string }>;
    articles: Array<{ slug: string; title: string }>;
  }> => {
    const response = await apiClient.get<{
      events: TimeTargetEvent[];
      plans: TimeTargetPlan[];
      pages?: Array<{ slug: string; title: string }>;
      articles?: Array<{ slug: string; title: string }>;
    }>('/api/admin/time-entries/targets');
    if (response.success && response.data) {
      return {
        events: response.data.events ?? [],
        plans: response.data.plans ?? [],
        pages: response.data.pages ?? [],
        articles: response.data.articles ?? [],
      };
    }

    return { events: [], plans: [], pages: [], articles: [] };
  },

  start: async (payload: TimeStartPayload): Promise<ApiResponse<{ entry: TimeEntry }>> => {
    return apiClient.post<{ entry: TimeEntry }>('/api/admin/time-entries/start', payload);
  },

  stop: async (note?: string): Promise<ApiResponse<{ entry: TimeEntry }>> => {
    return apiClient.post<{ entry: TimeEntry }>('/api/admin/time-entries/stop', note ? { note } : {});
  },

  updateNote: async (id: string, note: string): Promise<ApiResponse<{ entry: TimeEntry }>> => {
    return apiClient.put<{ entry: TimeEntry }>(`/api/admin/time-entries/${encodeURIComponent(id)}`, { note });
  },

  remove: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `/api/admin/time-entries/${encodeURIComponent(id)}`
    );
    return response.success;
  },
};
