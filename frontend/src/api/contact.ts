// frontend/src/api/contact.ts
import apiClient from './client';

export async function submitBrokenContentReport(payload: {
  name: string;
  email: string;
  message?: string;
  reportedUrl: string;
  referer?: string;
  _hp?: string;
}): Promise<{ ok: true; id: string } | { ok: false; error: string }> {
  const res = await apiClient.post<{ id: string }>('/api/contact', {
    name: payload.name,
    email: payload.email,
    message: payload.message ?? '',
    reportedUrl: payload.reportedUrl,
    referer: payload.referer ?? '',
    phonePrefix: '',
    phoneNumber: '',
    _hp: payload._hp ?? '',
  });
  if (res.success && res.data?.id) {
    return { ok: true, id: res.data.id };
  }
  return { ok: false, error: res.error ?? 'Failed to send report.' };
}

export async function submitContactForm(payload: {
  name: string;
  email: string;
  phonePrefix: string;
  phoneNumber: string;
  subject?: string;
  message: string;
  registrationRequest?: boolean;
  _hp?: string;
}): Promise<{ ok: true; id: string; message?: string } | { ok: false; error: string }> {
  const res = await apiClient.post<{ id: string }>('/api/contact', payload);
  if (res.success && res.data?.id) {
    return { ok: true, id: res.data.id, message: res.message };
  }
  return { ok: false, error: res.error ?? 'Failed to send message.' };
}
