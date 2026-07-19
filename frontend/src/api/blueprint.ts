// frontend/src/api/blueprint.ts
import apiClient from './client';

export interface BlueprintField {
  key: string;
  type: string;
  label: string;
  rules: string[];
  options?: string[];
  help?: string;
  default?: unknown;
}

export interface BlueprintDefinition {
  type: string;
  label: string;
  description: string;
  system: boolean;
  fields: BlueprintField[];
  updated_at?: string | null;
}

export interface BlueprintSummary {
  type: string;
  label: string;
  system: boolean;
  field_count: number;
}

export const blueprintApi = {
  async list() {
    const response = await apiClient.get<{ blueprints: BlueprintSummary[] }>('/api/admin/blueprints');
    return response.success && response.data ? response.data.blueprints : [];
  },

  async get(type: string) {
    const response = await apiClient.get<BlueprintDefinition>(
      `/api/admin/blueprints/${encodeURIComponent(type)}`
    );
    return response.success && response.data ? response.data : null;
  },

  async save(type: string, payload: Pick<BlueprintDefinition, 'label' | 'description' | 'fields'>) {
    const response = await apiClient.put<BlueprintDefinition>(
      `/api/admin/blueprints/${encodeURIComponent(type)}`,
      payload
    );
    return response.success ? response.data : null;
  },

  async validate(type: string, data: Record<string, unknown>) {
    const response = await apiClient.post<{ valid: boolean; validated: Record<string, unknown> }>(
      `/api/admin/blueprints/${encodeURIComponent(type)}/validate`,
      { data }
    );
    return response.success ? response.data : null;
  },
};
