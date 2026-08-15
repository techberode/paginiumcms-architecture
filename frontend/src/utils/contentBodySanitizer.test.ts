import { describe, expect, it } from 'vitest';
import { bodyLooksLikeMetadataLeak, stripEmbeddedMetadataLeak } from './contentBodySanitizer';

describe('contentBodySanitizer', () => {
  it('strips embedded yaml metadata block from article body', () => {
    const body = `# Article\n\nParagraph.\nseo:\n  title: beta38\nlocaleStatus:\n  sk: published`;
    expect(bodyLooksLikeMetadataLeak(body)).toBe(true);
    expect(stripEmbeddedMetadataLeak(body)).toBe('# Article\n\nParagraph.');
  });

  it('leaves normal markdown untouched', () => {
    const body = '# Title\n\n---\n\n## Section';
    expect(bodyLooksLikeMetadataLeak(body)).toBe(false);
    expect(stripEmbeddedMetadataLeak(body)).toBe(body);
  });
});
