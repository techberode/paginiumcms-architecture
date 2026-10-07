export const queryKeys = {
  dashboard: {
    stats: ['admin', 'dashboard', 'stats'] as const,
    overview: ['admin', 'dashboard', 'overview'] as const,
    secondary: ['admin', 'dashboard', 'secondary'] as const,
  },
  content: {
    list: (
      type: 'pages' | 'articles',
      params: {
        page: number;
        pageSize: number;
        search: string;
        status: string;
        tag: string;
        staleOnly: boolean;
        sortField: string;
        sortDirection: string;
      }
    ) => ['admin', 'content', type, 'list', params] as const,
  },
  extensions: {
    list: ['admin', 'extensions', 'list'] as const,
  },
  themes: {
    list: ['admin', 'themes', 'list'] as const,
    files: (themeId: string) => ['admin', 'themes', 'files', themeId] as const,
  },
  adminCounts: (userId: string | undefined) => ['admin', 'counts', userId ?? 'guest'] as const,
  projectPlanner: {
    overview: ['admin', 'project-planner', 'overview'] as const,
  },
  jobs: {
    overview: ['admin', 'jobs', 'overview'] as const,
  },
  metrics: {
    apmOverview: ['admin', 'metrics', 'apm', 'overview'] as const,
  },
  audit: {
    stats: (filtersKey: string) => ['admin', 'audit', 'stats', filtersKey] as const,
  },
};
