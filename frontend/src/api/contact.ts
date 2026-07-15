// frontend/src/api/contact.ts
import apiClient from './client';

export async function submitContactForm(payload: {
  name: string;
  email: string;
  subject?: string;
  message: string;
}): Promise<{ ok: true; id: string; message?: string } | { ok: false; error: string }> {
  const res = await apiClient.post<{ id: string }>('/api/contact', payload);
  if (res.success && res.data?.id) {
    return { ok: true, id: res.data.id, message: res.message };
  }
  return { ok: false, error: res.error ?? 'Failed to send message.' };
}
