import { apiClient } from './client';

export interface ContentLinkIssue {
  url: string;
  line: number;
  reason: string;
}

export interface ContentLinkCheckResult {
  ok: boolean;
  issues: ContentLinkIssue[];
}

export const contentEditorialApi = {
  linkCheck: async (payload: {
    type: 'page' | 'article';
    slug: string;
    body: string;
    contentFormat?: 'markdown' | 'html' | 'tiptap_json';
  }): Promise<ContentLinkCheckResult> => {
    const response = await apiClient.post<ContentLinkCheckResult>('/api/admin/content/link-check', payload);
    if (!response.success || !response.data) {
      throw new Error(response.error ?? 'Link check failed');
    }
    return response.data;
  },
};
