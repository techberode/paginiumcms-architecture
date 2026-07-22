import { describe, it, expect, beforeEach } from 'vitest';
import { formatReadingTime, resolveShowReadingTime } from './readingTime';
import { registerAllI18nModules } from '../i18n/registerModules';

describe('readingTime utils', () => {
  beforeEach(() => {
    registerAllI18nModules();
  });

  it('formats minutes in Slovak', () => {
    expect(formatReadingTime(1)).toBe('1 min čítania');
    expect(formatReadingTime(4)).toBe('4 min čítania');
  });

  it('formats minutes in English', () => {
    expect(formatReadingTime(1, 'en')).toBe('1 min read');
    expect(formatReadingTime(4, 'en')).toBe('4 min read');
  });

  it('resolves show flag from settings', () => {
    expect(resolveShowReadingTime({ showReadingTime: true })).toBe(true);
    expect(resolveShowReadingTime({ showReadingTime: false })).toBe(false);
    expect(resolveShowReadingTime(undefined)).toBe(true);
  });
});
