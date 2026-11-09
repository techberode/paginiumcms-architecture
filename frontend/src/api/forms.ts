import apiClient from './client';

export interface FormFieldDefinition {
  key: string;
  type: string;
  label: string;
  required?: boolean;
  placeholder?: string;
  minLength?: number | null;
  maxLength?: number | null;
  default?: string;
}

export interface FormDefinition {
  schemaVersion: number;
  id: string;
  title: string;
  description: string;
  enabled: boolean;
  preset?: string;
  routing: {
    channel: string;
    subject: string;
    priority: string;
  };
  fields: FormFieldDefinition[];
}

export interface FormListItem {
  id: string;
  title: string;
  enabled: boolean;
  preset: string;
  fieldCount: number;
  markup: string;
  widgetMarkup: string;
}

export const formsApi = {
  list: async () =>
    apiClient.get<{ forms: FormListItem[]; presets: string[] }>('/api/admin/forms'),

  get: async (id: string) => apiClient.get<{ form: FormDefinition }>(`/api/admin/forms/${encodeURIComponent(id)}`),

  createFromPreset: async (preset: string, id?: string) =>
    apiClient.post<{ form: FormDefinition }>('/api/admin/forms/presets', { preset, id: id ?? '' }),

  save: async (definition: FormDefinition) =>
    apiClient.put<{ form: FormDefinition }>(`/api/admin/forms/${encodeURIComponent(definition.id)}`, definition),

  remove: async (id: string) =>
    apiClient.delete<{ deleted: boolean }>(`/api/admin/forms/${encodeURIComponent(id)}`),
};
