import type { Page } from '../api/types';

export function isHomeTemplatePage(page: Pick<Page, 'slug' | 'template' | 'frontMatter'>): boolean {
  const fm = page.frontMatter ?? {};
  const template = String(page.template ?? fm.template ?? '');
  const slug = String(page.slug ?? '');
  return template === 'home' || slug === 'home' || slug === 'index';
}

/** Published page served at `/` (slug home/index, else first template home). */
export function resolveSiteHomePage(pages: Page[]): Page | undefined {
  const published = pages.filter((page) => page.status === 'published');
  const bySlug = published.find((page) => page.slug === 'home' || page.slug === 'index');
  if (bySlug) {
    return bySlug;
  }
  return published.find((page) => isHomeTemplatePage(page));
}

export function landingHeroPresentInBody(html?: string, content?: string): boolean {
  const hay = `${html ?? ''}\n${content ?? ''}`;
  return /pg-showcase-hero|pg-hero|\[showcase-hero|\[landing-hero/i.test(hay);
}
