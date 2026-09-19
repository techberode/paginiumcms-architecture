import { apiClient } from './client';

export interface ContentTranslationQuota {
  day: string;
  used: number;
  limit: number;
  remaining: number | null;
}

export interface ContentTranslationStatus {
  enabled: boolean;
  provider: string;
  quota: ContentTranslationQuota;
}

export interface ContentTranslationConnection {
  ok: boolean;
  provider?: string;
  error?: string;
}

export interface ContentTranslationLocaleResult {
  status: 'ok' | 'skipped' | 'failed';
  error?: string;
  fields?: Record<string, string>;
}

export interface ContentTranslationProposal {
  id: string;
  type: string;
  slug: string;
  sourceLocale: string;
  targetLocales: string[];
  fields: string[];
  sourceRevision: string;
  provider: string;
  characters: number;
  locales: Record<string, ContentTranslationLocaleResult>;
}

export interface ContentTranslationApplyResult {
  jobId: string;
  appliedLocales: string[];
  status: string;
  published: boolean;
  revision: string;
}

export const contentTranslationsApi = {
  status: async () => apiClient.get<ContentTranslationStatus>('/api/admin/content-translations/status'),

  testConnection: async () =>
    apiClient.post<ContentTranslationConnection>('/api/admin/content-translations/connection'),

  propose: async (
    type: string,
    slug: string,
    body: {
      sourceLocale: string;
      targetLocales: string[];
      fields?: string[];
      sourceRevision?: string;
    }
  ) =>
    apiClient.post<ContentTranslationProposal>(
      `/api/admin/content/${encodeURIComponent(type)}/${encodeURIComponent(slug)}/translations`,
      body
    ),

  get: async (jobId: string) =>
    apiClient.get<ContentTranslationProposal>(
      `/api/admin/content-translations/${encodeURIComponent(jobId)}`
    ),

  apply: async (jobId: string) =>
    apiClient.post<ContentTranslationApplyResult>(
      `/api/admin/content-translations/${encodeURIComponent(jobId)}/apply`
    ),

  discard: async (jobId: string) =>
    apiClient.delete<{ discarded: boolean }>(
      `/api/admin/content-translations/${encodeURIComponent(jobId)}`
    ),
};
