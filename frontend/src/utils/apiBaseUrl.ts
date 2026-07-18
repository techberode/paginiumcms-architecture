// frontend/src/utils/apiBaseUrl.ts
/**
 * Resolves the backend API base URL.
 *
 * Production (paginiumcms.com): when VITE_API_URL is unset, use same origin
 * so requests hit /api on the same host (requires nginx to proxy /api → PHP).
 *
 * Development: Vite proxy or explicit VITE_API_URL / localhost:8080 fallback.
 */
export function resolveApiBaseUrl(): string {
  const envUrl = import.meta.env.VITE_API_URL;
  if (envUrl && String(envUrl).trim() !== '') {
    return String(envUrl).replace(/\/$/, '');
  }

  if (typeof window !== 'undefined' && window.location?.origin) {
    return window.location.origin;
  }

  return 'http://localhost:8080';
}

/** Build same-origin URL for static storage paths (nginx proxies /storage/). */
export function resolveStorageUrl(url: string): string {
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }

  return url.startsWith('/') ? url : `/${url}`;
}

/** Authenticated admin preview URL — same origin, session cookies sent with <img>. */
export function resolveAdminMediaFileUrl(path: string): string {
  const encoded = path
    .split('/')
    .filter((segment) => segment.length > 0)
    .map((segment) => encodeURIComponent(segment))
    .join('/');

  return `/api/media/file/${encoded}`;
}

/** Build absolute URL for static media paths returned by the backend. */
export function resolveMediaUrl(url: string): string {
  if (url.startsWith('http://') || url.startsWith('https://')) {
    return url;
  }

  if (url.startsWith('/storage/') || url.startsWith('/api/media/file/')) {
    return resolveStorageUrl(url);
  }

  const base = resolveApiBaseUrl();
  const path = url.startsWith('/') ? url : `/${url}`;
  return `${base}${path}`;
}
