import { describe, expect, it } from 'vitest';
import type { Page } from '../api/types';
import { isHomeTemplatePage, landingHeroPresentInBody, resolveSiteHomePage } from './siteHomePage';

function page(partial: Partial<Page>): Page {
  return {
    id: 'x',
    title: 'T',
    slug: 'x',
    content: '',
    html: '',
    status: 'published',
    author: '',
    createdAt: '',
    updatedAt: '',
    frontMatter: {},
    ...partial,
  };
}

describe('siteHomePage', () => {
  it('resolves template home when slug is not home', () => {
    const pages = [page({ slug: 'paginium-cms', template: 'home' })];
    expect(resolveSiteHomePage(pages)?.slug).toBe('paginium-cms');
    expect(isHomeTemplatePage(pages[0]!)).toBe(true);
  });

  it('prefers slug home over template home', () => {
    const pages = [
      page({ slug: 'paginium-cms', template: 'home' }),
      page({ slug: 'home', template: 'default' }),
    ];
    expect(resolveSiteHomePage(pages)?.slug).toBe('home');
  });

  it('detects landing hero shortcodes in body', () => {
    expect(landingHeroPresentInBody('<section class="pg-showcase-hero"></section>', '')).toBe(true);
    expect(landingHeroPresentInBody('', '[landing-hero title="Hi"/]')).toBe(true);
    expect(landingHeroPresentInBody('<p>Hello</p>', '')).toBe(false);
  });
});
