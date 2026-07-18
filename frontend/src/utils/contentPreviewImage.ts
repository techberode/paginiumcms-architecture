import { resolveMediaUrl, resolvePublicMediaUrl } from '../api/media';

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

export function resolveContentPreviewImage(source: ContentImageSource): string {
  return resolveContentImageUrl(pickContentImageRaw(source));
}
