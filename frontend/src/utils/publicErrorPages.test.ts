import { describe, expect, it } from 'vitest';
import { normalizeErrorPageSlug, resolvePublicErrorPage } from './publicErrorPages';
import type { Page } from '../api/types';

describe('publicErrorPages', () => {
  it('normalizes slug from path', () => {
    expect(normalizeErrorPageSlug('/404-help')).toBe('404-help');
  });

  it('returns configured page when published', () => {
    const page = { slug: '404-help', title: 'Not found' } as Page;
    const getPageBySlug = (slug: string) => (slug === '404-help' ? page : undefined);
    const result = resolvePublicErrorPage(
      'notFound',
      { notFoundPageSlug: '404-help' },
      getPageBySlug,
      'missing'
    );
    expect(result.page).toBe(page);
  });
});
