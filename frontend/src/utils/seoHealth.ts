// frontend/src/utils/seoHealth.ts
import type { MediaFile } from '../api/media';

export type SeoHealthLevel = 'ok' | 'warning' | 'critical';

export type SeoIssueCode =
  | 'missing_description'
  | 'missing_seo_title'
  | 'missing_og_image'
  | 'missing_tags'
  | 'title_too_long'
  | 'description_too_long';

export interface SeoIssue {
  code: SeoIssueCode;
  level: 'warning' | 'critical';
}

export interface SeoHealthResult {
  level: SeoHealthLevel;
  issues: SeoIssue[];
}

export interface ContentSeoInput {
  status: string;
  frontMatter?: Record<string, unknown>;
  featuredImage?: string;
  tags?: string[];
  contentType?: 'page' | 'article';
}

export interface ContentSeoFormInput {
  status: string;
  seoTitle?: string;
  seoDescription?: string;
  ogImage?: string;
  tags?: string | string[];
  featuredImage?: string;
  /** When true, run publish-level checks even for drafts (editor preview). */
  checkAsPublished?: boolean;
  contentType?: 'page' | 'article';
}

function hasText(value: unknown): boolean {
  return typeof value === 'string' && value.trim() !== '';
}

function normalizeTags(tags: string | string[] | undefined): string[] {
  if (Array.isArray(tags)) {
    return tags.map((tag) => tag.trim()).filter(Boolean);
  }
  if (typeof tags === 'string') {
    return tags
      .split(',')
      .map((tag) => tag.trim())
      .filter(Boolean);
  }
  return [];
}

function mergeLevel(current: SeoHealthLevel, next: SeoIssue['level']): SeoHealthLevel {
  if (next === 'critical') {
    return 'critical';
  }
  if (current === 'critical') {
    return 'critical';
  }
  if (next === 'warning') {
    return 'warning';
  }
  return current;
}

function buildResult(issues: SeoIssue[]): SeoHealthResult {
  let level: SeoHealthLevel = 'ok';
  for (const issue of issues) {
    level = mergeLevel(level, issue.level);
  }

  return { level, issues };
}

function shouldCheckPublished(status: string, checkAsPublished?: boolean): boolean {
  return checkAsPublished === true || status === 'published' || status === 'scheduled';
}

export function getContentSeoHealth(item: ContentSeoInput): SeoHealthResult {
  const fm = item.frontMatter ?? {};
  const description = fm.seoDescription ?? fm.description ?? fm.metaDescription;
  const seoTitle = fm.seoTitle ?? fm.metaTitle;
  const ogImage = fm.seoImage ?? fm.ogImage ?? fm.featuredImage ?? item.featuredImage;
  const tags = item.tags ?? (Array.isArray(fm.tags) ? fm.tags : []);

  return getContentSeoHealthFromFields({
    status: item.status,
    seoTitle: typeof seoTitle === 'string' ? seoTitle : '',
    seoDescription: typeof description === 'string' ? description : '',
    ogImage: typeof ogImage === 'string' ? ogImage : '',
    tags,
    contentType: item.contentType ?? 'page',
  });
}

export function getContentSeoHealthFromFields(input: ContentSeoFormInput): SeoHealthResult {
  const issues: SeoIssue[] = [];
  const published = shouldCheckPublished(input.status, input.checkAsPublished);
  const tags = normalizeTags(input.tags);
  const seoTitle = (input.seoTitle ?? '').trim();
  const seoDescription = (input.seoDescription ?? '').trim();
  const ogImage = (input.ogImage ?? input.featuredImage ?? '').trim();

  if (published) {
    if (!hasText(seoDescription)) {
      issues.push({ code: 'missing_description', level: 'critical' });
    }
    if (!hasText(seoTitle)) {
      issues.push({ code: 'missing_seo_title', level: 'warning' });
    }
    if (!hasText(ogImage)) {
      issues.push({ code: 'missing_og_image', level: 'warning' });
    }
    if (input.contentType === 'article' && tags.length === 0) {
      issues.push({ code: 'missing_tags', level: 'warning' });
    }
  }

  if (seoTitle.length > 60) {
    issues.push({ code: 'title_too_long', level: 'warning' });
  }
  if (seoDescription.length > 160) {
    issues.push({ code: 'description_too_long', level: 'warning' });
  }

  return buildResult(issues);
}

export function evaluateContentSeo(item: ContentSeoInput): SeoHealthLevel {
  return getContentSeoHealth(item).level;
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
