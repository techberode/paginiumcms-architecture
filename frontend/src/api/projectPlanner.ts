import apiClient from './client';

export type ProjectPlanStatus = 'planned' | 'in_progress' | 'done' | 'skipped' | 'blocked';

export type ProjectPlanContentType = 'page' | 'article' | 'landing' | 'media' | 'newsletter' | 'custom';

export type ProjectPlanVarianceBadge = 'none' | 'early' | 'late' | 'on_time' | 'overdue' | 'due_soon';

export interface ProjectPlanPhase {
  id: string;
  title: string;
  sortOrder: number;
}

export interface ProjectPlanLinkedContent {
  type: ProjectPlanContentType;
  slug: string | null;
}

export interface ProjectPlanItem {
  id: string;
  phaseId: string | null;
  title: string;
  contentType: ProjectPlanContentType;
  dueAt: string | null;
  status: ProjectPlanStatus;
  linkedContent: ProjectPlanLinkedContent;
  completedAt: string | null;
  notes: string;
}

export interface ProjectPlanProgressSummary {
  percent: number;
  countedItems: number;
  doneCount: number;
  overdueCount: number;
  dueSoonCount: number;
}

export interface ProjectPlanItemVariance {
  itemId: string;
  badge: ProjectPlanVarianceBadge;
  days: number;
}

export interface ProjectPlanProgressDetail extends ProjectPlanProgressSummary {
  totalItems: number;
  earlyCount: number;
  lateCount: number;
  itemVariances: ProjectPlanItemVariance[];
}

export interface ProjectPlanSummary {
  schemaVersion: number;
  id: string;
  title: string;
  description: string;
  timezone: string;
  createdAt: string;
  updatedAt: string;
  createdBy: string;
  isDefault: boolean;
  phases: ProjectPlanPhase[];
  items: ProjectPlanItem[];
  progress: ProjectPlanProgressSummary;
}

export interface ProjectPlanDetail extends Omit<ProjectPlanSummary, 'progress'> {
  progress: ProjectPlanProgressDetail;
}

export interface ProjectPlanOverview {
  planCount: number;
  percent: number;
  countedItems: number;
  doneCount: number;
  overdueCount: number;
  dueSoonCount: number;
  nextDeadlines: Array<{
    planId: string;
    planTitle: string;
    itemId: string;
    title: string;
    contentType: ProjectPlanContentType;
    dueAt: string;
    badge: ProjectPlanVarianceBadge;
    days: number;
  }>;
}

export type ProjectPlanPayload = {
  id: string;
  title: string;
  description?: string;
  timezone?: string;
  isDefault?: boolean;
  phases?: ProjectPlanPhase[];
  items?: Array<Partial<ProjectPlanItem> & Pick<ProjectPlanItem, 'id' | 'title' | 'contentType' | 'status'>>;
};

export type ProjectPlanItemPayload = {
  id?: string;
  title: string;
  contentType?: ProjectPlanContentType;
  phaseId?: string | null;
  dueAt?: string | null;
  status?: ProjectPlanStatus;
  notes?: string;
  linkedContent?: ProjectPlanLinkedContent;
  completedAt?: string | null;
};

export const projectPlannerApi = {
  list: () =>
    apiClient.get<{ plans: ProjectPlanSummary[]; count: number }>('/api/admin/project-plans'),

  overview: () => apiClient.get<ProjectPlanOverview>('/api/admin/project-plans/overview'),

  get: (id: string) =>
    apiClient.get<ProjectPlanDetail>(`/api/admin/project-plans/${encodeURIComponent(id)}`),

  create: (payload: ProjectPlanPayload) =>
    apiClient.post<ProjectPlanDetail>('/api/admin/project-plans', payload),

  update: (id: string, payload: Partial<ProjectPlanPayload>) =>
    apiClient.patch<ProjectPlanDetail>(
      `/api/admin/project-plans/${encodeURIComponent(id)}`,
      payload
    ),

  addItem: (planId: string, payload: ProjectPlanItemPayload) =>
    apiClient.post<ProjectPlanDetail>(
      `/api/admin/project-plans/${encodeURIComponent(planId)}/items`,
      payload
    ),

  updateItem: (planId: string, itemId: string, payload: Partial<ProjectPlanItemPayload>) =>
    apiClient.patch<ProjectPlanDetail>(
      `/api/admin/project-plans/${encodeURIComponent(planId)}/items/${encodeURIComponent(itemId)}`,
      payload
    ),

  removeItem: (planId: string, itemId: string) =>
    apiClient.delete<ProjectPlanDetail>(
      `/api/admin/project-plans/${encodeURIComponent(planId)}/items/${encodeURIComponent(itemId)}`
    ),
};
