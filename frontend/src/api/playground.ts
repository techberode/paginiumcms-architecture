import { apiClient } from './client';

export type PlaygroundTemplate = 'react-ts' | 'vanilla' | 'vue';

export interface PlaygroundPackModule {
  id: string;
  sandpackEntry: string;
  capabilities: string[];
}

export interface PlaygroundPack {
  packId: string;
  title: string;
  source: { type: string };
  modules: PlaygroundPackModule[];
  enabled: boolean;
  files?: Record<string, string>;
}

export interface PlaygroundConfig {
  enabled: boolean;
  demoBlocked: boolean;
  defaultTemplate: PlaygroundTemplate;
  templates: PlaygroundTemplate[];
  packs: PlaygroundPack[];
  gitConfigured: boolean;
  gitRepoUrl: string;
  gitRef: string;
}

export const playgroundApi = {
  get: async (): Promise<PlaygroundConfig> => {
    const response = await apiClient.get<PlaygroundConfig>('/api/admin/playground');
    if (!response.success || response.data === undefined) {
      throw new Error(response.error ?? 'playground_load_failed');
    }
    return response.data;
  },

  importGit: async (): Promise<{ packId: string; title: string; enabled: boolean }> => {
    const response = await apiClient.post<{ packId: string; title: string; enabled: boolean }>(
      '/api/admin/playground/import-git',
      {}
    );
    if (!response.success || response.data === undefined) {
      throw new Error(response.error ?? 'playground_import_failed');
    }
    return response.data;
  },
};
