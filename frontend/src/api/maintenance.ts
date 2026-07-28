import apiClient from './client';

export type MaintenanceModeValue = 'off' | 'coming_soon' | 'under_maintenance';

export interface MaintenanceSettings {
  mode: MaintenanceModeValue;
  heroImageUrl?: string;
  newsletterEnabled?: boolean;
  newsletterHint?: string;
  comingSoonBadge?: string;
  comingSoonTitle?: string;
  comingSoonSubtitle?: string;
  comingSoonBody?: string;
  maintenanceBadge?: string;
  maintenanceTitle?: string;
  maintenanceSubtitle?: string;
  maintenanceBody?: string;
  maintenanceShowContactForm?: boolean;
  maintenanceContactSubject?: string;
}

export async function subscribeMaintenanceNewsletter(
  payload: {
    email: string;
    source: MaintenanceModeValue;
    preferences: import('./newsletter').NewsletterPreferenceKey[];
    consent?: boolean;
    _hp?: string;
  }
): Promise<{ ok: boolean; message?: string; error?: string; pending?: boolean }> {
  const res = await apiClient.post<{ id: string; created: boolean; merged?: boolean; pending?: boolean }>(
    '/api/maintenance/newsletter',
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

export async function sendMaintenanceMessage(payload: {
  name: string;
  email: string;
  message: string;
}): Promise<{ ok: boolean; message?: string; error?: string }> {
  const res = await apiClient.post<{ id: string }>('/api/maintenance/message', payload);

  if (res.success) {
    return { ok: true, message: res.message ?? undefined };
  }

  return { ok: false, error: res.error ?? 'Message send failed' };
}

export function isMaintenanceActive(mode?: MaintenanceModeValue | string | null): boolean {
  return mode === 'coming_soon' || mode === 'under_maintenance';
}
