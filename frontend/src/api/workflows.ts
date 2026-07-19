// frontend/src/api/workflows.ts
import apiClient from './client';

export interface OtpPendingResult {
  ok: true;
  requiresOtp: true;
  challengeId: string;
  expiresAt?: number;
  debugCode?: string;
}

export interface OtpVerifiedComment {
  ok: true;
  comment: import('./comments').Comment;
}

export interface OtpVerifiedPublish {
  ok: true;
  contentType: string;
  slug: string;
  status: string;
  title: string;
}

export type WorkflowOtpVerifyResult =
  | OtpVerifiedComment
  | OtpVerifiedPublish
  | { ok: false; error?: string };

function parseOtpPending(res: Record<string, unknown>): OtpPendingResult | null {
  if (res.success && res.requires_otp && typeof res.challenge_id === 'string') {
    return {
      ok: true,
      requiresOtp: true,
      challengeId: res.challenge_id,
      expiresAt: typeof res.expires_at === 'number' ? res.expires_at : undefined,
      debugCode: typeof res.debug_code === 'string' ? res.debug_code : undefined,
    };
  }
  return null;
}

export function extractOtpPending(res: Record<string, unknown>): OtpPendingResult | null {
  return parseOtpPending(res);
}

export async function verifyWorkflowOtp(challengeId: string, code: string): Promise<WorkflowOtpVerifyResult> {
  const res = (await apiClient.post<Record<string, unknown>>('/api/admin/workflows/otp/verify', {
    challenge_id: challengeId,
    code,
  })) as Record<string, unknown>;

  if (!res.success) {
    return { ok: false, error: (res.error as string | undefined) || 'Overenie kódu zlyhalo' };
  }

  if (res.comment && typeof res.comment === 'object') {
    return { ok: true, comment: res.comment as import('./comments').Comment };
  }

  if (typeof res.slug === 'string' && typeof res.content_type === 'string') {
    return {
      ok: true,
      contentType: String(res.content_type),
      slug: String(res.slug),
      status: String(res.status ?? 'published'),
      title: String(res.title ?? ''),
    };
  }

  return { ok: false, error: 'Neplatná odpoveď servera' };
}

export async function resendWorkflowOtp(
  challengeId: string
): Promise<OtpPendingResult | { ok: false; error?: string }> {
  const res = await apiClient.post<Record<string, unknown>>('/api/admin/workflows/otp/resend', {
    challenge_id: challengeId,
  });

  const pending = parseOtpPending(res as Record<string, unknown>);
  if (pending) {
    return pending;
  }

  return { ok: false, error: res.error || 'Odoslanie kódu zlyhalo' };
}
