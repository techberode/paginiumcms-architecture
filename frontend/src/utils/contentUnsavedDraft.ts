import type { ContentType } from '../api/drafts';

const PROVISIONAL_SLUG_RE = /^koncept-\d{8}-[a-z0-9]{4,8}$/;

/** Slug for auto-save before the first POST (flat-file draft only). */
export function createProvisionalDraftSlug(now = new Date()): string {
  const dd = String(now.getDate()).padStart(2, '0');
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const yyyy = String(now.getFullYear());
  const suffix = Math.random().toString(36).slice(2, 6);
  return `koncept-${dd}${mm}${yyyy}-${suffix}`;
}

export function isProvisionalDraftSlug(slug: string): boolean {
  return PROVISIONAL_SLUG_RE.test(slug.trim().toLowerCase());
}

export function defaultUnsavedDraftTitle(now = new Date()): string {
  const dd = String(now.getDate()).padStart(2, '0');
  const mm = String(now.getMonth() + 1).padStart(2, '0');
  const yyyy = String(now.getFullYear());
  return `koncept_${dd}${mm}${yyyy}`;
}

function stripMarkdownForTitle(raw: string): string {
  return raw
    .replace(/^#+\s+/gm, '')
    .replace(/[*_`~[\]()]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

/** Title stored in draft when the editor title field is empty. */
export function resolveAutoDraftTitle(title: string, content: string, now = new Date()): string {
  const trimmed = title.trim();
  if (trimmed !== '') {
    return trimmed;
  }

  const firstLine = stripMarkdownForTitle(content.split(/\r?\n/).find((line) => line.trim() !== '') ?? '');
  if (firstLine !== '') {
    const words = firstLine.split(/\s+/).slice(0, 8);
    return words.join(' ');
  }

  return defaultUnsavedDraftTitle(now);
}

export function editorPathForDraft(type: ContentType, slug: string): string {
  return type === 'article' ? `/articles/${encodeURIComponent(slug)}` : `/pages/${encodeURIComponent(slug)}`;
}
