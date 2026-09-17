import { describe, expect, it, vi } from 'vitest';
import { parsePageOutline, serializePageOutline } from './pageOutline';
import {
  createOutlineStarterPack,
  createPaletteCallout,
  createPaletteMarkdown,
  createPaletteShortcode,
  createPaletteVideo,
  isOutlinePaletteShortcode,
  moveOutlineBlock,
  newOutlineId,
  outlineStarterNames,
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

  it('builds a self-closing feature-gallery sample', () => {
    const block = createPaletteShortcode('feature-gallery');
    expect(block.kind).toBe('shortcode');
    if (block.kind !== 'shortcode') {
      return;
    }
    expect(block.selfClosing).toBe(true);
    expect(block.name).toBe('feature-gallery');
    expect(block.attrs.title).toBe('Selected work');
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

  it('round-trips an empty video block from the palette', () => {
    const block = createPaletteVideo();
    const again = parsePageOutline(serializePageOutline([block]));
    expect(again[0]).toMatchObject({ kind: 'video', src: '', poster: '' });
  });

  it('still generates an id when randomUUID throws (insecure HTTP)', () => {
    const spy = vi.spyOn(globalThis.crypto, 'randomUUID').mockImplementation(() => {
      throw new Error('Secure context required');
    });
    expect(newOutlineId()).toMatch(/^outline-/);
    expect(createPaletteVideo().id).toMatch(/^outline-/);
    spy.mockRestore();
  });

  it('recognizes palette names', () => {
    expect(isOutlinePaletteShortcode('landing-hero')).toBe(true);
    expect(isOutlinePaletteShortcode('feature-gallery')).toBe(true);
    expect(isOutlinePaletteShortcode('feature-card')).toBe(false);
  });

  it('builds portfolio and landing starter packs that round-trip', () => {
    expect(outlineStarterNames('portfolio')).toEqual(['landing-hero', 'feature-grid', 'cta-banner']);
    const pack = createOutlineStarterPack('portfolio');
    expect(pack.map((block) => (block.kind === 'shortcode' ? block.name : block.kind))).toEqual([
      'landing-hero',
      'feature-grid',
      'cta-banner',
    ]);
    const again = parsePageOutline(serializePageOutline(pack));
    expect(again.map((block) => (block.kind === 'shortcode' ? block.name : ''))).toEqual([
      'landing-hero',
      'feature-grid',
      'cta-banner',
    ]);

    const landing = createOutlineStarterPack('landing');
    expect(landing[0]).toMatchObject({ kind: 'shortcode', name: 'showcase-hero' });
  });

  it('reorders outline rows without dropping items', () => {
    expect(moveOutlineBlock(['a', 'b', 'c'], 0, 2)).toEqual(['b', 'c', 'a']);
    expect(moveOutlineBlock(['a', 'b', 'c'], 2, 0)).toEqual(['c', 'a', 'b']);
    expect(moveOutlineBlock(['a', 'b', 'c'], 1, 1)).toEqual(['a', 'b', 'c']);
    expect(moveOutlineBlock(['a', 'b', 'c'], -1, 1)).toEqual(['a', 'b', 'c']);
  });
});
