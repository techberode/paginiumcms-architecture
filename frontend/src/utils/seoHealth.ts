// frontend/src/utils/seoHealth.ts
import type { MediaFile } from '../api/media';

export type SeoHealthLevel = 'ok' | 'warning' | 'critical';

export interface ContentSeoInput {
  status: string;
  frontMatter?: Record<string, unknown>;
  featuredImage?: string;
  tags?: string[];
}

function hasText(value: unknown): boolean {
  return typeof value === 'string' && value.trim() !== '';
}

export function evaluateMediaSeo(file: MediaFile): SeoHealthLevel {
  if (file.mimeType.startsWith('image/') && !hasText(file.altText)) {
    return 'critical';
  }

  if (!hasText(file.title) && !hasText(file.altText)) {
    return 'warning';
  }

  return 'ok';
}

export function evaluateContentSeo(item: ContentSeoInput): SeoHealthLevel {
  const fm = item.frontMatter ?? {};
  const description = fm.seoDescription ?? fm.description ?? fm.metaDescription;
  const ogImage = fm.seoImage ?? fm.ogImage ?? fm.featuredImage ?? item.featuredImage;
  const tags = item.tags ?? (Array.isArray(fm.tags) ? fm.tags : []);

  if (item.status === 'published') {
    if (!hasText(description)) {
      return 'critical';
    }
    if (!hasText(ogImage)) {
      return 'warning';
    }
  }

  if (Array.isArray(tags) && tags.length === 0 && item.status === 'published') {
    return 'warning';
  }

  if (!hasText(fm.seoTitle ?? fm.metaTitle) && item.status === 'published') {
    return 'warning';
  }

  return 'ok';
}

export function seoHealthLabel(level: SeoHealthLevel): string {
  switch (level) {
    case 'ok':
      return 'SEO OK';
    case 'warning':
      return 'SEO warning';
    case 'critical':
      return 'SEO issue';
    default:
      return level;
  }
}
