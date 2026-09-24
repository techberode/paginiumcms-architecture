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
});
