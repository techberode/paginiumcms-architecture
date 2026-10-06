/** Allow-listed iframe hosts for lightbox embed slides (58f-i-j). */

const ALLOWED_HOSTS = new Set(['www.youtube-nocookie.com', 'player.vimeo.com']);

export function isAllowListedLightboxEmbedSrc(src: string): boolean {
  const trimmed = src.trim();
  if (trimmed === '') {
    return false;
  }
  try {
    const parsed = new URL(trimmed);
    if (parsed.protocol !== 'https:') {
      return false;
    }
    return ALLOWED_HOSTS.has(parsed.hostname);
  } catch {
    return false;
  }
}
