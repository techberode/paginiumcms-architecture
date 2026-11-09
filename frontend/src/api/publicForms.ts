import { apiClient } from './client';

export interface PublicFormField {
  key: string;
  type: string;
  label: string;
  required: boolean;
  placeholder: string;
  minLength: number | null;
  maxLength: number | null;
}

export interface PublicFormSchema {
  id: string;
  title: string;
  description: string;
  fields: PublicFormField[];
}

export async function fetchPublicFormSchema(formId: string): Promise<PublicFormSchema | null> {
  const response = await apiClient.get<PublicFormSchema>(`/api/public/forms/${encodeURIComponent(formId)}`);
  return response.success && response.data ? response.data : null;
}

export async function submitPublicForm(
  formId: string,
  body: Record<string, unknown>
): Promise<{ ok: boolean; error?: string }> {
  const response = await apiClient.post<{ id: string }>(
    `/api/public/forms/${encodeURIComponent(formId)}/submit`,
    body
  );
  if (response.success) {
    return { ok: true };
  }

  return { ok: false, error: response.error || response.message || 'Submit failed' };
}
