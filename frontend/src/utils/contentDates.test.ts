import { describe, it, expect, beforeEach } from 'vitest';
import { formatContentDateLabels, formatDisplayDate, formatDisplayDateTime, formatRelativeTime } from './contentDates';
import { registerAllI18nModules } from '../i18n/registerModules';

describe('formatContentDateLabels', () => {
  beforeEach(() => {
    registerAllI18nModules();
  });

  it('uses front matter date as created', () => {
    const labels = formatContentDateLabels({
      frontMatterDate: '2026-02-18',
      createdAt: '2026-01-01',
      updatedAt: '2026-01-01',
    });
    expect(labels.primaryTitle).toBe('Vytvorené');
    expect(labels.primary).toContain('2026');
    expect(labels.secondary).toBeUndefined();
  });

  it('shows updated badge when edited later', () => {
    const labels = formatContentDateLabels({
      createdAt: '2026-01-01T10:00:00Z',
      updatedAt: '2026-02-18T12:00:00Z',
    });
    expect(labels.secondaryTitle).toBe('Upravené');
    expect(labels.secondary).toBeTruthy();
  });

  it('uses English labels when locale is en', () => {
    const labels = formatContentDateLabels(
      {
        createdAt: '2026-01-01T10:00:00Z',
        updatedAt: '2026-02-18T12:00:00Z',
      },
      'en'
    );
    expect(labels.primaryTitle).toBe('Created');
    expect(labels.secondaryTitle).toBe('Updated');
  });

  it('formatDisplayDate returns em dash for invalid values', () => {
    expect(formatDisplayDate(undefined)).toBe('—');
    expect(formatDisplayDate('undefined')).toBe('—');
    expect(formatDisplayDate('not-a-date')).toBe('—');
    expect(formatDisplayDate('2026-02-18')).toContain('2026');
  });

  it('formatDisplayDateTime returns em dash for invalid values', () => {
    expect(formatDisplayDateTime(undefined)).toBe('—');
    expect(formatDisplayDateTime('2026-02-18T12:00:00Z')).toContain('2026');
  });

  it('formatRelativeTime returns em dash for invalid values', () => {
    expect(formatRelativeTime('')).toBe('—');
    expect(formatRelativeTime(undefined)).toBe('—');
    expect(formatRelativeTime('2020-01-01T12:00:00Z')).not.toBe('—');
  });
});
