import { describe, expect, it } from 'vitest';
import {
  resolvePageHero,
  resolvePageHeroPlacement,
  resolvePageHeroPreviewImages,
  stripHeroDuplicateFromBody,
  stripHeroDuplicateFromHtml,
} from './pageHero';

describe('pageHero', () => {
  it('resolves placement overrides and auto rules', () => {
    const base = { mode: 'single' as const, placement: 'auto' as const, images: [], focusX: 50, focusY: 50 };
    expect(
      resolvePageHeroPlacement(
        { ...base, placement: 'intro-card' },
        { embed: false, isHome: false, isLandingLayout: false }
      )
    ).toBe('intro-card');
    expect(resolvePageHeroPlacement(base, { embed: true, isHome: false, isLandingLayout: false })).toBe(
      'intro-card'
    );
    expect(resolvePageHeroPlacement(base, { embed: false, isHome: false, isLandingLayout: true })).toBe(
      'landing-inline'
    );
    expect(resolvePageHeroPlacement(base, { embed: false, isHome: true, isLandingLayout: true })).toBe(
      'landing-inline'
    );
  });

  it('preview images fall back to SEO in auto mode', () => {
    expect(
      resolvePageHeroPreviewImages(
        { mode: 'auto', placement: 'auto', images: [], focusX: 50, focusY: 50 },
        '/storage/seo.jpg'
      )
    ).toEqual(['/storage/seo.jpg']);
  });

  it('auto mode uses og image', () => {
    const resolved = resolvePageHero(
      { ogImage: '/storage/media/a.jpg', frontMatter: {} },
      { mode: 'auto', placement: 'auto', images: [], focusX: 50, focusY: 50 }
    );
    expect(resolved.showImage).toBe(true);
    expect(resolved.images[0]).toBe('/storage/media/a.jpg');
  });

  it('strips duplicate markdown hero line from body', () => {
    const body = '![Blog](/storage/media/a.jpg)\n\nHello';
    const next = stripHeroDuplicateFromBody(body, ['/storage/media/a.jpg']);
    expect(next).toBe('Hello');
  });

  it('strips duplicate hero img from stored html', () => {
    const html = '<p>![Blog](/storage/media/a.jpg)</p><p>Hello</p>';
    const next = stripHeroDuplicateFromHtml(html, ['/storage/media/a.jpg']);
    expect(next).toBe('<p>Hello</p>');
  });
});
