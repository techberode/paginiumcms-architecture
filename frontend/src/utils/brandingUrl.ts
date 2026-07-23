import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../api/media';

/** Resolve branding/media URL for display (public site + admin). */
export function resolveBrandingUrl(url: string | undefined | null): string {
  const raw = String(url ?? '').trim();
  if (!raw) {
    return '';
  }

  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/api/')) {
    return raw;
  }

  if (raw.startsWith('/storage/')) {
    return resolvePublicMediaUrl(raw);
  }

  if (raw.startsWith('media/')) {
    return resolveAdminMediaPreviewUrl(raw);
  }

  if (raw.startsWith('/')) {
    return raw;
  }

  return raw;
}
