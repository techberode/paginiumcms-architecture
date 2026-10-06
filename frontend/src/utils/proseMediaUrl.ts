/** Allow-listed prose media URLs (images + DAM video). */

export function isProseAllowListedMediaPath(pathname: string): boolean {
  if (!pathname.startsWith('/')) {
    return false;
  }
  return pathname.startsWith('/storage/') || pathname.startsWith('/api/media/file/');
}

export function isProseAllowListedMediaSrc(src: string): boolean {
  const trimmed = src.trim();
  if (trimmed === '') {
    return false;
  }
  if (isProseAllowListedMediaPath(trimmed)) {
    return true;
  }
  try {
    const parsed = new URL(trimmed, 'http://localhost');
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
      return false;
    }
    return isProseAllowListedMediaPath(parsed.pathname);
  } catch {
    return false;
  }
}

export function proseMediaFullSizeSrc(src: string): string {
  if (!isProseAllowListedMediaSrc(src)) {
    return '';
  }
  try {
    const parsed = new URL(src, 'http://localhost');
    parsed.searchParams.delete('w');
    const path = `${parsed.pathname}${parsed.search}${parsed.hash}`;
    if (src.trim().startsWith('http://') || src.trim().startsWith('https://')) {
      return parsed.toString();
    }
    return path;
  } catch {
    return src.split('?')[0] ?? '';
  }
}
