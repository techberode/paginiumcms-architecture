import { describe, expect, it } from 'vitest';
import { parsePageOutline, serializePageOutline } from './pageOutline';
import {
  createPaletteCallout,
  createPaletteMarkdown,
  createPaletteShortcode,
  createPaletteVideo,
  isOutlinePaletteShortcode,
} from './outlinePalette';

describe('outlinePalette', () => {
  it('builds a landing-hero sample that round-trips', () => {
    const block = createPaletteShortcode('landing-hero');
    expect(block.kind).toBe('shortcode');
    if (block.kind !== 'shortcode') {
      return;
    }
    expect(block.selfClosing).toBe(true);
    expect(block.attrs.title).toBeTruthy();

    const again = parsePageOutline(serializePageOutline([block]));
    expect(again[0]).toMatchObject({ kind: 'shortcode', name: 'landing-hero' });
  });

  it('keeps nested cards inside feature-grid innerMarkdown', () => {
    const block = createPaletteShortcode('feature-grid');
    expect(block.kind).toBe('shortcode');
    if (block.kind !== 'shortcode') {
      return;
    }
    expect(block.innerMarkdown).toContain('feature-card');
  });

  it('creates core non-shortcode blocks', () => {
    expect(createPaletteMarkdown('Hello').kind).toBe('markdown');
    expect(createPaletteVideo().kind).toBe('video');
    expect(createPaletteCallout('tip')).toMatchObject({ kind: 'callout', calloutType: 'tip' });
  });

  it('recognizes palette names', () => {
    expect(isOutlinePaletteShortcode('landing-hero')).toBe(true);
    expect(isOutlinePaletteShortcode('feature-card')).toBe(false);
  });
});
