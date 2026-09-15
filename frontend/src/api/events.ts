import apiClient, { type ApiResponse } from './client';

export const EVENT_STATUSES = ['draft', 'published'] as const;

export type EventStatus = (typeof EVENT_STATUSES)[number];

export interface SiteEvent {
  schema: string;
  id: string;
  title: string;
  slug: string;
  startsAt: number;
  endsAt: number | null;
  location: string;
  body: string;
  status: EventStatus;
  projectPlanId: string | null;
  createdAt: number;
  updatedAt: number;
}

export interface EventsIndex {
  events: SiteEvent[];
  statuses: EventStatus[];
}

export interface EventPayload {
  title: string;
  slug?: string;
  startsAt: number;
  endsAt?: number | null;
  location?: string;
  body?: string;
  status?: EventStatus;
  projectPlanId?: string | null;
}

function emptyIndex(): EventsIndex {
  return { events: [], statuses: [...EVENT_STATUSES] };
}

export const eventsApi = {
  list: async (): Promise<EventsIndex> => {
    const response = await apiClient.get<EventsIndex>('/api/admin/events');
    if (response.success && response.data) {
      return {
        events: response.data.events ?? [],
        statuses: response.data.statuses ?? [...EVENT_STATUSES],
      };
    }

    return emptyIndex();
  },

  create: async (payload: EventPayload): Promise<ApiResponse<{ event: SiteEvent }>> => {
    return apiClient.post<{ event: SiteEvent }>('/api/admin/events', payload);
  },

  update: async (id: string, payload: Partial<EventPayload>): Promise<ApiResponse<{ event: SiteEvent }>> => {
    return apiClient.put<{ event: SiteEvent }>(`/api/admin/events/${encodeURIComponent(id)}`, payload);
  },

  remove: async (id: string): Promise<boolean> => {
    const response = await apiClient.delete<{ removed: boolean }>(
      `/api/admin/events/${encodeURIComponent(id)}`
    );
    return response.success;
  },
};
