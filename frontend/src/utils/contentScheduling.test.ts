import { describe, expect, it } from 'vitest';
import {
  datetimeLocalToIso,
  isoToDatetimeLocalValue,
  resolveScheduledAtForSave,
} from './contentScheduling';

describe('contentScheduling utils', () => {
  it('converts datetime-local to ISO and back in local timezone', () => {
    const local = '2026-07-15T12:30';
    const iso = datetimeLocalToIso(local);

    expect(iso).not.toBe('');
    expect(isoToDatetimeLocalValue(iso)).toBe(local);
  });

  it('returns empty strings for invalid values', () => {
    expect(isoToDatetimeLocalValue('')).toBe('');
    expect(isoToDatetimeLocalValue('invalid')).toBe('');
    expect(datetimeLocalToIso('')).toBe('');
  });

  it('resolveScheduledAtForSave sends ISO for scheduled status', () => {
    const local = '2026-08-01T09:00';
    expect(resolveScheduledAtForSave('scheduled', local)).toBe(datetimeLocalToIso(local));
  });

  it('resolveScheduledAtForSave sends empty string when unscheduling draft', () => {
    expect(resolveScheduledAtForSave('draft', '')).toBe('');
  });
});
