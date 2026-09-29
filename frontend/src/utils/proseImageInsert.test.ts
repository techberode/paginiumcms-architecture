import { describe, expect, it } from 'vitest';
import { buildInlineImageMarkup } from './proseImageInsert';

describe('buildInlineImageMarkup', () => {
  it('uses markdown when lightbox is enabled', () => {
    expect(buildInlineImageMarkup('/storage/a.png', 'Alt', true)).toContain('![Alt]');
  });

  it('uses data-lightbox off when disabled', () => {
    const snippet = buildInlineImageMarkup('/storage/a.png', 'Alt', false);
    expect(snippet).toContain('data-lightbox="off"');
    expect(snippet).not.toContain('![');
  });

  it('wraps captioned images in figure with figcaption', () => {
    const snippet = buildInlineImageMarkup('/storage/a.png', 'Alt', true, 'Obr. 1.1');
    expect(snippet).toContain('<figure class="paginium-figure">');
    expect(snippet).toContain('<figcaption>Obr. 1.1</figcaption>');
  });

  it('places caption above image when requested', () => {
    const snippet = buildInlineImageMarkup('/storage/a.png', 'Alt', true, 'Obr. 1.1', 'above');
    expect(snippet).toContain('paginium-figure--caption-top');
    expect(snippet.indexOf('<figcaption>')).toBeLessThan(snippet.indexOf('<img'));
  });
});
