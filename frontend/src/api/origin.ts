import { apiClient } from './client';

export type FeatureProbeStatus = 'implemented' | 'partial' | 'missing' | 'unknown';

export interface OriginFeatureProbe {
  id: string;
  group: string;
  labelKey: string;
  status: FeatureProbeStatus;
  message: string;
  since: string | null;
}

export interface OriginProbeSummary {
  implemented: number;
  partial: number;
  missing: number;
  unknown: number;
  total: number;
}

export interface OriginCatalogItem {
  id: string;
  titleKey: string;
  probeId: string | null;
  phase: string;
  status: FeatureProbeStatus;
  percentComplete: number;
  runtimeMessage: string | null;
}

export interface OriginCatalogIteration {
  id: string;
  titleKey: string;
  phase: string;
  since: string | null;
  doc: string;
  priority: string;
  percentComplete: number;
  items: OriginCatalogItem[];
  history: Array<{ version: string; date: string; summaryKey: string }>;
}

export interface OriginCatalogProgress {
  percent: number;
  shipped: number;
  partial: number;
  planned: number;
  total: number;
}

export interface OriginTimelineEntry {
  version: string;
  date: string;
  summaryKey: string;
}

export interface OriginCatalog {
  schemaVersion: number;
  updatedAt: string;
  progress: OriginCatalogProgress;
  iterations: OriginCatalogIteration[];
  timeline: OriginTimelineEntry[];
}

export interface OriginOverview {
  health: Record<string, unknown>;
  counts: Record<string, number>;
  probes: OriginFeatureProbe[];
  summary: OriginProbeSummary;
  catalog: OriginCatalog;
}

export const originApi = {
  overview: () => apiClient.get<OriginOverview>('/api/admin/origin/overview'),
  probes: () =>
    apiClient.get<{ probes: OriginFeatureProbe[]; summary: OriginProbeSummary; catalog: OriginCatalog }>(
      '/api/admin/origin/probes'
    ),
  catalog: () => apiClient.get<OriginCatalog>('/api/admin/origin/catalog'),
};
