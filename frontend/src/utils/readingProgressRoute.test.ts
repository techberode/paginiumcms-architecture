import { describe, expect, it } from 'vitest';
import { shouldShowReadingProgress } from './readingProgressRoute';

describe('shouldShowReadingProgress', () => {
  it('respects disabled setting', () => {
    expect(shouldShowReadingProgress('/blog/post-one', false)).toBe(false);
  });

  it('enables on article detail', () => {
    expect(shouldShowReadingProgress('/blog/post-one', true)).toBe(true);
    expect(shouldShowReadingProgress('/blog/post-one/', undefined)).toBe(true);
  });

  it('skips blog index and home', () => {
    expect(shouldShowReadingProgress('/blog', true)).toBe(false);
    expect(shouldShowReadingProgress('/', true)).toBe(false);
  });

  it('enables on top-level pages', () => {
    expect(shouldShowReadingProgress('/about', true)).toBe(true);
    expect(shouldShowReadingProgress('/reference-agency', true)).toBe(true);
  });
});
