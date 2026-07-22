import type { Locale } from '../i18n';
import { translate } from '../i18n';

export interface AuditEventLike {
  display_message?: string;
  message?: string;
  timestamp?: string | number;
  context?: {
    summary?: string;
    action?: string;
    target?: string;
    category?: string;
    timestamp?: string;
    user?: { name?: string; email?: string };
    metadata?: Record<string, unknown>;
  };
  log?: {
    display_message?: string;
    message?: string;
    context?: AuditEventLike['context'];
  };
}

function pickDisplayMessage(event: AuditEventLike): string | undefined {
  const direct = event.display_message?.trim();
  if (direct) {
    return direct;
  }

  const nested = event.log?.display_message?.trim();
  if (nested) {
    return nested;
  }

  return undefined;
}

export function formatAuditEventMessage(
  event: AuditEventLike,
  locale: Locale = 'sk'
): string {
  const fromApi = pickDisplayMessage(event);
  if (fromApi) {
    return fromApi;
  }

  const summary = event.context?.summary ?? event.log?.context?.summary;
  if (typeof summary === 'string' && summary.trim() !== '') {
    return summary;
  }

  if (typeof event.message === 'string' && event.message.trim() !== '') {
    return event.message;
  }

  if (typeof event.log?.message === 'string' && event.log.message.trim() !== '') {
    return event.log.message;
  }

  return translate(locale, 'audit.system_event');
}

export function formatAuditEventActor(event: AuditEventLike, locale: Locale = 'sk'): string {
  const name = event.context?.user?.name?.trim() ?? event.log?.context?.user?.name?.trim();
  if (name) {
    return name;
  }

  const email = event.context?.user?.email?.trim() ?? event.log?.context?.user?.email?.trim();
  if (email) {
    return email;
  }

  return translate(locale, 'audit.system');
}

export function formatAuditEventTimestamp(
  event: AuditEventLike,
  locale: Locale = 'sk'
): string {
  const raw = event.timestamp ?? event.context?.timestamp ?? event.log?.context?.timestamp;

  if (raw === undefined || raw === null || raw === '') {
    return '';
  }

  const date = typeof raw === 'number' ? new Date(raw * 1000) : new Date(String(raw));
  if (Number.isNaN(date.getTime())) {
    return '';
  }

  return date.toLocaleString(locale === 'en' ? 'en-US' : 'sk-SK');
}
