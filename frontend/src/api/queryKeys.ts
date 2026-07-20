export const queryKeys = {
  dashboard: {
    stats: ['admin', 'dashboard', 'stats'] as const,
  },
  content: {
    list: (
      type: 'pages' | 'articles',
      params: {
        page: number;
        pageSize: number;
        search: string;
        status: string;
        sortField: string;
        sortDirection: string;
      }
    ) => ['admin', 'content', type, 'list', params] as const,
  },
  extensions: {
    list: ['admin', 'extensions', 'list'] as const,
  },
  adminCounts: (userId: string | undefined) => ['admin', 'counts', userId ?? 'guest'] as const,
};
