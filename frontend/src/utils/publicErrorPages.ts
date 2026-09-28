import type { Page } from '../api/types';

export type PublicSystemErrorKind = 'notFound' | 'serverError';

export function normalizeErrorPageSlug(raw: unknown): string {
  if (typeof raw !== 'string') {
    return '';
  }
  const slug = raw.trim().replace(/^\/+/, '').split('/')[0] ?? '';
  return slug.length > 120 ? slug.slice(0, 120) : slug;
}

export function resolvePublicErrorPage(
  kind: PublicSystemErrorKind,
  layout: { notFoundPageSlug?: string; serverErrorPageSlug?: string } | undefined,
  getPageBySlug: (slug: string) => Page | undefined,
  missingSlug?: string
): { page?: Page; slug: string; missingSlug?: string } {
  const configured =
    kind === 'notFound'
      ? normalizeErrorPageSlug(layout?.notFoundPageSlug)
      : normalizeErrorPageSlug(layout?.serverErrorPageSlug);

  if (configured !== '') {
    const page = getPageBySlug(configured);
    if (page) {
      return { page, slug: configured, missingSlug };
    }
  }

  return { slug: configured, missingSlug };
}
