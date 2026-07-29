// frontend/src/api/analyticsPageview.ts
/** Public SPA pageview beacon — no auth, CSRF-exempt. */

export interface PageviewPayload {
  uri: string;
  duration_seconds?: number;
}

export async function trackPublicPageview(payload: PageviewPayload): Promise<void> {
  try {
    await fetch('/api/analytics/pageview', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
      keepalive: true,
    });
  } catch {
    // Non-blocking — analytics must not break public navigation
  }
}
