// frontend/src/api/content.ts
// === Content API (pages & articles) – Iteration 21 scaffold ===
import apiClient, { PaginatedResponse, PaginationMeta } from './client';
import type { Article, Page } from './types';

export type ContentType = 'pages' | 'articles';
export type ContentItem = Page | Article;

export interface ListContentParams {
  page?: number;
  per_page?: number;
  search?: string;
  status?: string;
}

function endpoint(type: ContentType): string {
  return type === 'articles' ? '/api/articles' : '/api/pages';
}

export interface SuggestMetaPayload {
  type: 'page' | 'article';
  title: string;
  body: string;
  bodyFormat: 'markdown' | 'html' | 'tiptap_json';
  existingTags?: string[];
}

export interface SuggestMetaResponse {
  tags: string[];
  description: string;
}

export interface RenderPreviewPayload {
  body: string;
  bodyFormat: 'markdown' | 'html' | 'tiptap_json';
  cachedHtml?: string;
}

export interface RenderPreviewResponse {
  html: string;
}

export const contentApi = {
  list: async <T extends ContentItem = Page>(
    type: ContentType,
    params: ListContentParams = {}
  ): Promise<{ items: T[]; meta?: PaginationMeta }> => {
    const searchParams = new URLSearchParams();
    if (params.page) {
      searchParams.set('page', String(params.page));
    }
    if (params.per_page) {
      searchParams.set('per_page', String(params.per_page));
    }
    if (params.search) {
      searchParams.set('search', params.search);
    }
    if (params.status) {
      searchParams.set('status', params.status);
    }

    const qs = searchParams.toString();
    const url = qs ? `${endpoint(type)}?${qs}` : endpoint(type);
    const res = await apiClient.get<T[]>(url);

    return {
      items: res.success && Array.isArray(res.data) ? res.data : [],
      meta: (res as PaginatedResponse<T>).meta,
    };
  },

  get: async <T extends ContentItem = Page>(type: ContentType, slug: string): Promise<T | null> => {
    const res = await apiClient.get<T>(`${endpoint(type)}/${encodeURIComponent(slug)}`);
    return res.success && res.data ? res.data : null;
  },

  create: async (type: ContentType, payload: Record<string, unknown>): Promise<ContentItem | null> => {
    const res = await apiClient.post<ContentItem>(endpoint(type), payload);
    return res.success && res.data ? res.data : null;
  },

  update: async (
    type: ContentType,
    slug: string,
    payload: Record<string, unknown>
  ): Promise<ContentItem | null> => {
    const res = await apiClient.put<ContentItem>(
      `${endpoint(type)}/${encodeURIComponent(slug)}`,
      payload
    );
    return res.success && res.data ? res.data : null;
  },

  delete: async (type: ContentType, slug: string): Promise<boolean> => {
    const res = await apiClient.delete(`${endpoint(type)}/${encodeURIComponent(slug)}`);
    return Boolean(res.success);
  },

  bulkDelete: async (type: ContentType, slugs: string[]) => {
    const res = await apiClient.post<import('../types/bulk').BulkBatchResult>(
      `${endpoint(type)}/bulk-delete`,
      { slugs }
    );
    return res.success && res.data ? res.data : null;
  },

  bulkUpdateStatus: async (
    type: ContentType,
    slugs: string[],
    status: 'draft' | 'published' | 'archived' | 'scheduled'
  ) => {
    const res = await apiClient.patch<import('../types/bulk').BulkBatchResult>(
      `${endpoint(type)}/bulk-status`,
      { slugs, status }
    );
    return res.success && res.data ? res.data : null;
  },

  suggestMeta: async (payload: SuggestMetaPayload): Promise<SuggestMetaResponse> => {
    const res = await apiClient.post<SuggestMetaResponse>('/api/admin/content/suggest-meta', payload);
    if (!res.success || !res.data) {
      throw new Error('suggest_meta_failed');
    }
    return res.data;
  },

  renderPreview: async (payload: RenderPreviewPayload): Promise<string> => {
    const res = await apiClient.post<RenderPreviewResponse>('/api/admin/content/render-preview', payload);
    if (!res.success || !res.data?.html) {
      throw new Error('render_preview_failed');
    }
    return res.data.html;
  },
};
