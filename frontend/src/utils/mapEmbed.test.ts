import { describe, it, expect } from 'vitest';
import { isSafeMapEmbedUrl } from './mapEmbed';

describe('isSafeMapEmbedUrl', () => {
  it('accepts google maps embed https URL', () => {
    expect(
      isSafeMapEmbedUrl('https://www.google.com/maps/embed?pb=abc123')
    ).toBe(true);
  });

  it('rejects non-google hosts', () => {
    expect(isSafeMapEmbedUrl('https://evil.example/maps/embed')).toBe(false);
  });

  it('rejects empty values', () => {
    expect(isSafeMapEmbedUrl('')).toBe(false);
    expect(isSafeMapEmbedUrl(null)).toBe(false);
  });
});
