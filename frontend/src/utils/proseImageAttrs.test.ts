import { describe, expect, it } from 'vitest';
import {
  buildProseImageDataAttrs,
  normalizeProseGalleryGroup,
  proseImageRequiresHtmlMarkup,
} from './proseImageAttrs';

describe('proseImageAttrs', () => {
  it('normalizes gallery group slug', () => {
    expect(normalizeProseGalleryGroup('  chapter-1  ')).toBe('chapter-1');
    expect(normalizeProseGalleryGroup('bad<script>')).toBe('badscript');
    expect(normalizeProseGalleryGroup('')).toBe('');
  });

  it('builds gallery and slideshow attrs', () => {
    expect(buildProseImageDataAttrs(true, { galleryGroup: 'hero', excludeFromSlideshow: true })).toBe(
      ' data-gallery="hero" data-slideshow="off"'
    );
    expect(buildProseImageDataAttrs(false)).toBe(' data-lightbox="off"');
  });

  it('requires HTML when group or slideshow opts set', () => {
    expect(proseImageRequiresHtmlMarkup(true, undefined, { galleryGroup: 'a' })).toBe(true);
    expect(proseImageRequiresHtmlMarkup(true, undefined, { excludeFromSlideshow: true })).toBe(true);
    expect(proseImageRequiresHtmlMarkup(true, undefined)).toBe(false);
  });
});
