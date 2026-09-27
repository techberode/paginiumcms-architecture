import apiClient from './client';

export type CmsMigrationSource = {
  id: string;
  label: string;
  input: string;
  hint: string;
};

export type ContentMigrationResult = {
  created: number;
  skipped: number;
  messages: string[];
  errors: string[];
  success: boolean;
};

export const contentMigrationApi = {
  listSources: async (): Promise<CmsMigrationSource[]> => {
    const response = await apiClient.get<{ sources?: CmsMigrationSource[] }>(
      '/api/admin/content-migration/sources'
    );
    return response.success && Array.isArray(response.data?.sources) ? response.data.sources : [];
  },

  importFile: async (
    file: File,
    format: string,
    run: boolean
  ): Promise<ContentMigrationResult | null> => {
    const formData = new FormData();
    formData.append('file', file);

    const response = await apiClient.post<ContentMigrationResult>(
      `/api/admin/content-migration/import?format=${encodeURIComponent(format)}&run=${run ? '1' : '0'}`,
      formData,
      { timeout: 120000 }
    );

    return response.data ?? null;
  },
};
