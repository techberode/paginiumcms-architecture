/** Parse "Name <a@b.com>, c@d.com" into unique validated emails. */
export function parseAngledEmail(segment: string): string {
  const trimmed = segment.trim();
  if (trimmed === '') {
    return '';
  }
  const angled = trimmed.match(/<([^>]+)>/);
  if (angled?.[1] !== undefined && angled[1].trim() !== '') {
    return angled[1].trim();
  }
  return trimmed.includes('@') ? trimmed : '';
}

export function parseRecipientList(raw: string): string[] {
  const parts = raw
    .split(/[,;\n]+/)
    .map((part) => parseAngledEmail(part))
    .filter((part) => part !== '');

  const out: string[] = [];
  for (const email of parts) {
    if (!out.includes(email)) {
      out.push(email);
    }
  }

  return out;
}

export function isValidEmailAddress(email: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

export function validateRecipientList(raw: string): { ok: true; recipients: string[] } | { ok: false } {
  const segments = raw
    .split(/[,;\n]+/)
    .map((part) => part.trim())
    .filter((part) => part !== '');

  if (segments.length === 0) {
    return { ok: false };
  }

  const recipients: string[] = [];
  for (const segment of segments) {
    const email = parseAngledEmail(segment);
    if (email === '' || !isValidEmailAddress(email)) {
      return { ok: false };
    }
    if (!recipients.includes(email)) {
      recipients.push(email);
    }
  }

  return { ok: true, recipients };
}

export function formatRecipientList(recipients: string[]): string {
  return recipients.join(', ');
}

const RECENT_KEY_PREFIX = 'paginium.mail.recentRecipients.';

export function readRecentRecipients(mailbox: string): string[] {
  if (mailbox === '' || typeof window === 'undefined') {
    return [];
  }
  try {
    const raw = window.localStorage.getItem(RECENT_KEY_PREFIX + mailbox.toLowerCase());
    if (raw === null || raw === '') {
      return [];
    }
    const parsed: unknown = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
      return [];
    }
    return parsed.filter((item): item is string => typeof item === 'string' && isValidEmailAddress(item));
  } catch {
    return [];
  }
}

export function rememberRecipients(mailbox: string, recipients: string[], limit = 40): void {
  if (mailbox === '' || recipients.length === 0 || typeof window === 'undefined') {
    return;
  }
  const key = RECENT_KEY_PREFIX + mailbox.toLowerCase();
  const merged = [...recipients];
  for (const item of readRecentRecipients(mailbox)) {
    if (!merged.includes(item)) {
      merged.push(item);
    }
  }
  try {
    window.localStorage.setItem(key, JSON.stringify(merged.slice(0, limit)));
  } catch {
    // ignore quota / private mode
  }
}

export function collectMailboxAddressHints(
  messages: Array<{ from?: string; to?: string }>,
  recent: string[]
): string[] {
  const set = new Set<string>(recent);
  for (const message of messages) {
    for (const field of [message.from, message.to]) {
      if (field === undefined || field === '') {
        continue;
      }
      for (const part of field.split(/[,;\n]+/)) {
        const email = parseAngledEmail(part);
        if (email !== '' && isValidEmailAddress(email)) {
          set.add(email);
        }
      }
    }
  }
  return Array.from(set).sort((a, b) => a.localeCompare(b));
}
