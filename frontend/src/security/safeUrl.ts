// frontend/src/security/safeUrl.ts
const BLOCKED_PROTOCOLS = ['javascript:', 'data:', 'vbscript:'] as const;

/**
 * Povolí relatívne cesty, hash anchor a http(s) URL.
 * Blokuje nebezpečné protokoly (XSS cez href/src).
 */
export function isSafeNavigationUrl(url: string): boolean {
  const trimmed = url.trim();
  if (trimmed === '' || trimmed.startsWith('/') || trimmed.startsWith('#')) {
    return true;
  }

  const lower = trimmed.toLowerCase();
  if (BLOCKED_PROTOCOLS.some((protocol) => lower.startsWith(protocol))) {
    return false;
  }

  return lower.startsWith('http://') || lower.startsWith('https://');
}

/**
 * Vráti bezpečnú URL alebo fallback (default '/'), ak vstup nie je povolený.
 */
export function sanitizeNavigationUrl(url: string, fallback = '/'): string {
  return isSafeNavigationUrl(url) ? url.trim() : fallback;
}
