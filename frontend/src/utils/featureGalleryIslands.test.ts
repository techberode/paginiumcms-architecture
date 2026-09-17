import { describe, expect, it } from 'vitest';
import { hasFeatureGalleryIsland, splitFeatureGalleryHtml } from './featureGalleryIslands';

describe('splitFeatureGalleryHtml', () => {
  it('keeps plain HTML as a single fragment', () => {
    expect(splitFeatureGalleryHtml('<p>Hello</p>')).toEqual([{ kind: 'html', html: '<p>Hello</p>' }]);
    expect(hasFeatureGalleryIsland('<p>Hello</p>')).toBe(false);
  });

  it('extracts gallery islands and surrounding markup', () => {
    const html =
      '<p>Before</p><section class="pg-feature-gallery" data-tag="web" data-title="Work"><p>grid</p></section><p>After</p>';
    const parts = splitFeatureGalleryHtml(html);

    expect(parts).toEqual([
      { kind: 'html', html: '<p>Before</p>' },
      { kind: 'gallery', tag: 'web', title: 'Work' },
      { kind: 'html', html: '<p>After</p>' },
    ]);
    expect(hasFeatureGalleryIsland(html)).toBe(true);
  });

  it('reads attributes when class is not first', () => {
    const html =
      '<section data-title="A &amp; B" class="pg-feature-gallery extra" data-tag="print"></section>';
    expect(splitFeatureGalleryHtml(html)).toEqual([
      { kind: 'gallery', tag: 'print', title: 'A & B' },
    ]);
  });
});
