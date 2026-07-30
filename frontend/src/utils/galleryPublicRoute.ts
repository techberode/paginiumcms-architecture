/**
 * Normalize gallery publicRoute from Settings (single path segment recommended).
 * Examples: "/features", "features", "/funkcie/" → "/features" | "/funkcie"
 */
export function normalizeGalleryPublicPath(route?: string | null): string {
  const raw = (route ?? '/features').trim();
  if (raw === '' || raw === '/') {
    return '/features';
  }
  let path = raw.startsWith('/') ? raw : `/${raw}`;
  if (path.length > 1 && path.endsWith('/')) {
    path = path.slice(0, -1);
  }
  // Collapse accidental doubles; keep first segment only for SPA :slug matching
  const segments = path.split('/').filter(Boolean);
  if (segments.length === 0) {
    return '/features';
  }
  return `/${segments[0]}`;
}

export function galleryPublicSlug(route?: string | null): string {
  return normalizeGalleryPublicPath(route).replace(/^\//, '');
}

export function isGalleryPublicPath(pathname: string, route?: string | null): boolean {
  return normalizeGalleryPublicPath(pathname) === normalizeGalleryPublicPath(route);
}
