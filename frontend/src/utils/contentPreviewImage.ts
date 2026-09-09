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

/** Raw path/URL from API or front matter (first non-empty wins). */
export function pickContentImageRaw(source: ContentImageSource): string {
  const fm = source.frontMatter ?? {};

  const candidates: unknown[] = [
    source.featuredImage,
    source.ogImage,
    fm.seoImage,
    fm.featuredImage,
    fm.featured_image,
    fm.ogImage,
  ];

  for (const candidate of candidates) {
    if (typeof candidate === 'string' && candidate.trim() !== '') {
      return candidate.trim();
    }
  }

  return '';
}

/** Browser-ready URL for public `<img src>` (same-origin /storage or absolute). */
export function resolveContentImageUrl(raw: string): string {
  const value = raw.trim();
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
