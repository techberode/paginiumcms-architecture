import { describe, it, expect } from 'vitest';
import { formatReadingTime, resolveShowReadingTime } from './readingTime';

describe('readingTime utils', () => {
  it('formats minutes in Slovak', () => {
    expect(formatReadingTime(1)).toBe('1 min čítania');
    expect(formatReadingTime(4)).toBe('4 min čítania');
  });

  it('resolves show flag from settings', () => {
    expect(resolveShowReadingTime({ showReadingTime: true })).toBe(true);
    expect(resolveShowReadingTime({ showReadingTime: false })).toBe(false);
    expect(resolveShowReadingTime(undefined)).toBe(true);
  });
});
