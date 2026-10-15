import {
  appendThumbnailQuery,
  resolveMediaUrl,
  resolvePublicMediaUrl,
} from '../api/media';

type ContentImageSource = {
  featuredImage?: string | null;
  ogImage?: string | null;
  frontMatter?: Record<string, unknown> | null;
};

/** True when the value looks like a media path/URL, not human alt/caption text stored by mistake. */
export function isContentImageReference(raw: string): boolean {
  const value = raw.trim();
  if (value === '') {
    return false;
  }
  if (/^https?:\/\//i.test(value)) {
    return true;
  }
  if (value.startsWith('/')) {
    return true;
  }
  if (value.startsWith('media/') || value.startsWith('content/media/')) {
    return true;
  }

  return /\.(avif|gif|jpe?g|png|svg|webp)(\?[^#]*)?(#.*)?$/i.test(value);
}

/** Raw path/URL from API or front matter (first non-empty wins). */
export function pickContentImageRaw(source: ContentImageSource): string {
  const fm = source.frontMatter ?? {};

  // SEO / OG image is the editor source of truth; legacy featuredImage may be stale after restore.
  const candidates: unknown[] = [
    fm.seoImage,
    source.ogImage,
    fm.ogImage,
    source.featuredImage,
    fm.featuredImage,
    fm.featured_image,
  ];

  for (const candidate of candidates) {
    if (typeof candidate === 'string') {
      const trimmed = candidate.trim();
      if (trimmed !== '' && isContentImageReference(trimmed)) {
        return trimmed;
      }
    }
  }

  return '';
}

/** Map admin-only media file URLs to public /storage/ (anonymous blog must not use /api/media/file). */
export function normalizePublicContentImageUrl(raw: string): string {
  const value = raw.trim();
  if (!value.startsWith('/api/media/file/')) {
    return value;
  }

  const encoded = value.slice('/api/media/file/'.length);
  let path = decodeURIComponent(encoded).replace(/^\/+/, '');
  if (path.startsWith('content/media/')) {
    path = path.slice('content/'.length);
  }
  if (!path.startsWith('media/')) {
    path = `media/${path}`;
  }

  return `/storage/app/content/${path}`;
}

/** Browser-ready URL for public `<img src>` (same-origin /storage or absolute). */
export function resolveContentImageUrl(raw: string): string {
  let value = normalizePublicContentImageUrl(raw.trim());
  if (value === '') {
    return '';
  }

  if (value.startsWith('http://') || value.startsWith('https://')) {
    return value;
  }

  if (value.startsWith('/storage/')) {
    return resolvePublicMediaUrl(value);
  }

  if (value.startsWith('media/')) {
    return resolvePublicMediaUrl(`/storage/app/content/${value}`);
  }

  if (value.startsWith('content/media/')) {
    return resolvePublicMediaUrl(`/storage/app/${value}`);
  }

  return resolveMediaUrl(value);
}

export function resolveContentPreviewImage(source: ContentImageSource, width = 0): string {
  const url = resolveContentImageUrl(pickContentImageRaw(source));

  return width > 0 ? appendThumbnailQuery(url, width) : url;
}

/**
 * Comma-separated `srcset` for same-origin `/storage/` images (It.87a).
 * Remote URLs are skipped — the backend `?w=` pipeline only applies to storage.
 */
export function buildResponsiveSrcSet(url: string, widths: number[]): string {
  const value = url.trim();
  if (value === '' || widths.length === 0) {
    return '';
  }
  if ((value.startsWith('http://') || value.startsWith('https://')) && !value.includes('/storage/')) {
    return '';
  }

  const unique = [...new Set(widths.filter((width) => width > 0))].sort((a, b) => a - b);
  return unique.map((width) => `${appendThumbnailQuery(value, width)} ${width}w`).join(', ');
}

export function resolveContentPreviewSrcSet(source: ContentImageSource, widths: number[]): string {
  return buildResponsiveSrcSet(resolveContentImageUrl(pickContentImageRaw(source)), widths);
}

/**
 * Safe `background-image` value for CSS custom properties.
 * Quoted via JSON.stringify so a path cannot break out of `url(...)`.
 */
export function toCssBackgroundImage(url: string): string {
  const value = url.trim();
  if (value === '') {
    return '';
  }

  if (!/^(https?:\/\/|\/)/i.test(value)) {
    return '';
  }

  return `url(${JSON.stringify(value)})`;
}
