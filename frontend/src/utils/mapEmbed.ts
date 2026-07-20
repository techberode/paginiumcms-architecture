/** Validates Google Maps iframe embed URLs for safe public rendering. */

export function isSafeMapEmbedUrl(url: unknown): url is string {
  if (typeof url !== 'string' || url.trim() === '') {
    return false;
  }

  try {
    const parsed = new URL(url.trim());
    return (
      parsed.protocol === 'https:' &&
      parsed.hostname === 'www.google.com' &&
      parsed.pathname.startsWith('/maps/embed')
    );
  } catch {
    return false;
  }
}
