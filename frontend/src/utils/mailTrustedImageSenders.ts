const STORAGE_PREFIX = 'paginium.mail.trustedImageSenders:';

/** Parse RFC5322 From into a lowercase e-mail for trust matching. */
export function parseSenderEmail(from: string): string {
  const trimmed = from.trim();
  const angle = trimmed.match(/<([^>]+)>/);
  if (angle?.[1] !== undefined) {
    return angle[1].trim().toLowerCase();
  }

  return trimmed.toLowerCase();
}

function storageKey(mailbox: string): string {
  return STORAGE_PREFIX + mailbox.trim().toLowerCase();
}

export function readTrustedImageSenders(mailbox: string): string[] {
  if (mailbox.trim() === '' || typeof localStorage === 'undefined') {
    return [];
  }

  try {
    const raw = localStorage.getItem(storageKey(mailbox));
    if (raw === null || raw === '') {
      return [];
    }
    const parsed: unknown = JSON.parse(raw);
    if (!Array.isArray(parsed)) {
      return [];
    }

    return parsed.filter((item): item is string => typeof item === 'string' && item.includes('@'));
  } catch {
    return [];
  }
}

export function isTrustedImageSender(mailbox: string, fromHeader: string): boolean {
  const email = parseSenderEmail(fromHeader);
  if (!email.includes('@')) {
    return false;
  }

  return readTrustedImageSenders(mailbox).includes(email);
}

export function addTrustedImageSender(mailbox: string, fromHeader: string): string {
  const email = parseSenderEmail(fromHeader);
  if (!email.includes('@')) {
    return email;
  }

  const current = new Set(readTrustedImageSenders(mailbox));
  current.add(email);
  localStorage.setItem(storageKey(mailbox), JSON.stringify([...current].sort()));

  return email;
}

/** First opt-in to remote images for a sender — persisted per mailbox in this browser. */
export function rememberSenderRemoteImages(mailbox: string, fromHeader: string): string {
  return addTrustedImageSender(mailbox, fromHeader);
}
