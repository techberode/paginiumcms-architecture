export const BUILTIN_COOKIE_POLICY_PATH = '/cookies';

export function resolveCookiePolicyHref(rawUrl: string): { href: string; external: boolean } {
  const trimmed = rawUrl.trim();
  if (trimmed === '') {
    return { href: BUILTIN_COOKIE_POLICY_PATH, external: false };
  }

  if (trimmed.startsWith('/')) {
    return { href: trimmed, external: false };
  }

  return { href: trimmed, external: true };
}
