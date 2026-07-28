import apiClient from './client';

export type NewsletterPreferenceKey =
  | 'weekly_digest'
  | 'new_article'
  | 'cms_release'
  | 'general_news';

export interface NewsletterSubscriber {
  id: string;
  email: string;
  subscribedAt: string;
  source: string;
  preferences?: NewsletterPreferenceKey[];
  status?: string;
  consentAt?: string | null;
}

export interface NewsletterSubscribersResponse {
  items: NewsletterSubscriber[];
  count: number;
  bySource: Record<string, number>;
}

export interface NewsletterSendStatus {
  configured: boolean;
  sendEnabled: boolean;
  weeklyDigestEnabled: boolean;
  newArticleEnabled: boolean;
  cmsReleaseEnabled: boolean;
  lastWeeklyDigestAt: string | null;
}

export interface NewsletterSendResult {
  sent: number;
  failed: number;
  skipped: number;
  reason?: string;
}

export interface NewsletterSubscribePayload {
  email: string;
  preferences: NewsletterPreferenceKey[];
  consent?: boolean;
  _hp?: string;
}

export async function subscribeFooterNewsletter(
  payload: NewsletterSubscribePayload
): Promise<{ ok: boolean; message?: string; error?: string; pending?: boolean }> {
  const res = await apiClient.post<{ id: string; created: boolean; merged?: boolean; pending?: boolean }>(
    '/api/newsletter/subscribe',
    payload
  );

  if (res.success) {
    return {
      ok: true,
      message: res.message ?? undefined,
      pending: res.data?.pending === true,
    };
  }

  return { ok: false, error: res.error ?? res.message ?? 'Newsletter subscription failed' };
}

export async function confirmNewsletterSubscription(
  token: string
): Promise<{ ok: boolean; message?: string }> {
  const res = await apiClient.get<{ confirmed: boolean }>(
    `/api/newsletter/confirm?token=${encodeURIComponent(token)}`
  );

  return {
    ok: res.success === true,
    message: res.message ?? res.error,
  };
}

export async function unsubscribeNewsletter(
  token: string,
  preference?: NewsletterPreferenceKey
): Promise<{ ok: boolean; message?: string; fullyUnsubscribed?: boolean }> {
  const query = new URLSearchParams({ token });
  if (preference) {
    query.set('preference', preference);
  }

  const res = await apiClient.get<{ unsubscribed: boolean; fullyUnsubscribed?: boolean }>(
    `/api/newsletter/unsubscribe?${query.toString()}`
  );

  return {
    ok: res.success === true,
    message: res.message ?? res.error,
    fullyUnsubscribed: res.data?.fullyUnsubscribed,
  };
}

export interface NewsletterManageData {
  emailMasked: string;
  preferences: NewsletterPreferenceKey[];
  status: string;
  enabledPreferences: NewsletterPreferenceKey[];
  requireConsentCheckbox: boolean;
}

export async function fetchNewsletterManage(
  token: string
): Promise<{ ok: boolean; message?: string; data?: NewsletterManageData }> {
  const res = await apiClient.get<NewsletterManageData>(
    `/api/newsletter/manage?token=${encodeURIComponent(token)}`
  );

  return {
    ok: res.success === true,
    message: res.message ?? res.error,
    data: res.data ?? undefined,
  };
}

export async function updateNewsletterManage(
  token: string,
  preferences: NewsletterPreferenceKey[]
): Promise<{
  ok: boolean;
  message?: string;
  data?: { preferences: NewsletterPreferenceKey[]; status: string };
}> {
  const res = await apiClient.post<{ preferences: NewsletterPreferenceKey[]; status: string }>(
    '/api/newsletter/manage',
    { token, preferences }
  );

  return {
    ok: res.success === true,
    message: res.message ?? res.error,
    data: res.data ?? undefined,
  };
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

export async function fetchNewsletterSendStatus(): Promise<NewsletterSendStatus | null> {
  const res = await apiClient.get<NewsletterSendStatus>('/api/admin/newsletter/send/status');
  return res.success && res.data ? res.data : null;
}

export async function sendNewsletterWeeklyDigestNow(): Promise<{
  ok: boolean;
  message?: string;
  data?: NewsletterSendResult;
}> {
  const res = await apiClient.post<NewsletterSendResult>('/api/admin/newsletter/send/weekly-digest');
  return {
    ok: res.success === true,
    message: res.message ?? res.error,
    data: res.data ?? undefined,
  };
}

export async function sendNewsletterTestEmail(email: string): Promise<{ ok: boolean; message?: string }> {
  const res = await apiClient.post<unknown>('/api/admin/newsletter/send/test', { email });
  return {
    ok: res.success === true,
    message: res.message ?? res.error,
  };
}

export interface CmsReleasePayload {
  version?: string;
  title: string;
  body: string;
  url?: string;
}

export async function sendNewsletterCmsRelease(
  payload: CmsReleasePayload
): Promise<{ ok: boolean; message?: string; data?: NewsletterSendResult }> {
  const res = await apiClient.post<NewsletterSendResult>('/api/admin/newsletter/send/cms-release', payload);
  return {
    ok: res.success === true,
    message: res.message ?? res.error,
    data: res.data ?? undefined,
  };
}
