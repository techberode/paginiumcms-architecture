// frontend/src/security/safeUrl.ts
// Browsers strip tabs/newlines inside schemes (`java\tscript:`) and treat `/\host` as `//host`.
// eslint-disable-next-line no-control-regex
const UNSAFE_CHARS = /[\u0000-\u001f\u007f\\]/;

/**
 * Povolí relatívne cesty, hash anchor a http(s) URL.
 * Blokuje nebezpečné protokoly (XSS cez href/src) a protocol-relative `//host`.
 */
export function isSafeNavigationUrl(url: string): boolean {
  const trimmed = url.trim();
  if (trimmed === '' || trimmed.startsWith('#')) {
    return true;
  }
  if (UNSAFE_CHARS.test(trimmed)) {
    return false;
  }
  if (trimmed.startsWith('/')) {
    return !trimmed.startsWith('//');
  }

  const lower = trimmed.toLowerCase();
  return lower.startsWith('http://') || lower.startsWith('https://');
}

/**
 * Vráti bezpečnú URL alebo fallback (default '/'), ak vstup nie je povolený.
 */
export function sanitizeNavigationUrl(url: string, fallback = '/'): string {
  return isSafeNavigationUrl(url) ? url.trim() : fallback;
}

/**
 * Vráti bezpečnú URL alebo `undefined` — pre voliteľné odkazy, ktoré sa pri nebezpečnom vstupe nemajú renderovať.
 */
export function safeOptionalHref(url: string | null | undefined): string | undefined {
  const trimmed = url?.trim() ?? '';
  if (trimmed === '' || !isSafeNavigationUrl(trimmed)) {
    return undefined;
  }
  return trimmed;
}
