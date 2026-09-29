import { describe, expect, it } from 'vitest';
import {
  parseArticleHeroFocus,
  parsePageHeroSettings,
  resolvePageHero,
  resolvePageHeroPlacement,
  resolvePageHeroRenderFlags,
  resolvePageHeroPreviewImages,
  stripHeroDuplicateFromBody,
  stripHeroDuplicateFromHtml,
} from './pageHero';

describe('pageHero', () => {
  it('maps landing-inline on non-landing layout to header band', () => {
    const settings = {
      mode: 'auto' as const,
      placement: 'landing-inline' as const,
      images: [],
      focusX: 50,
      focusY: 50,
      fit: 'contain' as const,
    };
    const flags = resolvePageHeroRenderFlags(settings, {
      embed: false,
      isHome: false,
      isLandingLayout: false,
    });
    expect(flags.placement).toBe('landing-inline');
    expect(flags.landingInlineShell).toBe(false);
    expect(flags.headerBand).toBe(true);
  });

  it('resolves placement overrides and auto rules', () => {
    const base = {
      mode: 'single' as const,
      placement: 'auto' as const,
      images: [],
      focusX: 50,
      focusY: 50,
      fit: 'contain' as const,
    };
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
        { mode: 'auto', placement: 'auto', images: [], focusX: 50, focusY: 50, fit: 'contain' },
        '/storage/seo.jpg'
      )
    ).toEqual(['/storage/seo.jpg']);
  });

  it('auto mode uses og image', () => {
    const resolved = resolvePageHero(
      { ogImage: '/storage/media/a.jpg', frontMatter: {} },
      { mode: 'auto', placement: 'auto', images: [], focusX: 50, focusY: 50, fit: 'contain' }
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

  it('parses page hero fit from shared front matter keys', () => {
    const settings = parsePageHeroSettings({
      frontMatter: { heroFit: 'contain', heroFocusX: '20', heroFocusY: '80' },
    });
    expect(settings.fit).toBe('contain');
    expect(settings.focusX).toBe(20);
    expect(settings.focusY).toBe(80);
  });

  it('parses article hero focus and fit from front matter', () => {
    const focus = parseArticleHeroFocus({ heroFocusX: '30', heroFocusY: '70', heroFit: 'contain' });
    expect(focus.focusX).toBe(30);
    expect(focus.focusY).toBe(70);
    expect(focus.fit).toBe('contain');
  });

  it('defaults article hero to contain when crop not configured', () => {
    expect(parseArticleHeroFocus({}).fit).toBe('contain');
    expect(parseArticleHeroFocus({ heroFocusX: '40' }).fit).toBe('cover');
  });
});
