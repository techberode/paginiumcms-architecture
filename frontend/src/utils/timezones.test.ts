import { describe, expect, it } from 'vitest';
import {
  buildTimezoneOptions,
  filterTimezoneOptions,
  formatTimezoneLabel,
  getCommonTimezoneOptions,
  isDaylightSavingActive,
  isValidTimezone,
} from './timezones';

describe('timezones', () => {
  it('validates known timezone ids', () => {
    expect(isValidTimezone('Europe/Bratislava')).toBe(true);
    expect(isValidTimezone('Not/A/Zone')).toBe(false);
  });

  it('builds options with readable labels', () => {
    const options = buildTimezoneOptions('sk', ['Europe/Bratislava']);
    const bratislava = options.find((option) => option.id === 'Europe/Bratislava');
    expect(bratislava).toBeTruthy();
    expect(bratislava?.label.length).toBeGreaterThan(0);
  });

  it('filters options by city or id', () => {
    const options = buildTimezoneOptions('en');
    const filtered = filterTimezoneOptions(options, 'bratislava');
    expect(filtered.some((option) => option.id === 'Europe/Bratislava')).toBe(true);
  });

  it('returns common timezone shortcuts', () => {
    const common = getCommonTimezoneOptions('sk');
    expect(common.some((option) => option.id === 'UTC')).toBe(true);
    expect(common.some((option) => option.id === 'Europe/Bratislava')).toBe(true);
  });

  it('formats timezone label without throwing', () => {
    expect(formatTimezoneLabel('UTC', 'en')).toContain('UTC');
  });

  it('detects daylight saving status for Europe/Bratislava in July', () => {
    const summer = new Date('2026-07-15T12:00:00Z');
    expect(isDaylightSavingActive('Europe/Bratislava', summer)).toBe(true);
  });
});
