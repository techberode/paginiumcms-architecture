import apiClient from './client';

export interface NewsletterSubscriber {
  id: string;
  email: string;
  subscribedAt: string;
  source: string;
}

export interface NewsletterSubscribersResponse {
  items: NewsletterSubscriber[];
  count: number;
  bySource: Record<string, number>;
}

export async function subscribeFooterNewsletter(
  email: string
): Promise<{ ok: boolean; message?: string; error?: string }> {
  const res = await apiClient.post<{ id: string; created: boolean }>('/api/newsletter/subscribe', {
    email,
    _hp: '',
  });

  if (res.success) {
    return { ok: true, message: res.message ?? undefined };
  }

  return { ok: false, error: res.error ?? 'Newsletter subscription failed' };
}

export async function listNewsletterSubscribers(): Promise<NewsletterSubscribersResponse> {
  const res = await apiClient.get<NewsletterSubscribersResponse>('/api/admin/newsletter/subscribers');
  return res.success && res.data
    ? res.data
    : { items: [], count: 0, bySource: {} };
}

export async function exportNewsletterSubscribersCsv(): Promise<Blob> {
  const response = await apiClient.get('/api/admin/newsletter/subscribers/export', {
    responseType: 'blob',
  });
  return response.data as Blob;
}
