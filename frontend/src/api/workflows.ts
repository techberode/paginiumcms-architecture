// frontend/src/api/workflows.ts
import apiClient from './client';
import type { Comment } from './comments';

export interface OtpPendingResult {
  ok: true;
  requiresOtp: true;
  challengeId: string;
  expiresAt?: number;
  debugCode?: string;
}

export interface OtpVerifiedComment {
  ok: true;
  comment: Comment;
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
  const resRaw = await apiClient.post<Record<string, unknown>>('/api/admin/workflows/otp/verify', {
    challenge_id: challengeId,
    code,
  });
  const res = resRaw as unknown as {
    success?: boolean;
    error?: unknown;
    comment?: unknown;
    slug?: unknown;
    content_type?: unknown;
    status?: unknown;
    title?: unknown;
  };

  if (!res.success) {
    return {
      ok: false,
      error: (typeof res.error === 'string' ? res.error : undefined) || 'Overenie kódu zlyhalo',
    };
  }

  if (res.comment && typeof res.comment === 'object') {
    return { ok: true, comment: res.comment as Comment };
  }

  if (typeof res.slug === 'string' && typeof res.content_type === 'string') {
    return {
      ok: true,
      contentType: res.content_type,
      slug: res.slug,
      status: typeof res.status === 'string' ? res.status : 'published',
      title: typeof res.title === 'string' ? res.title : '',
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

  const pending = parseOtpPending(res as unknown as Record<string, unknown>);
  if (pending) {
    return pending;
  }

  return { ok: false, error: res.error || 'Odoslanie kódu zlyhalo' };
}
