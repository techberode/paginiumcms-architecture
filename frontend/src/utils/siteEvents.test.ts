import { describe, expect, it } from 'vitest';
import {
  datetimeLocalToUnix,
  eventTouchesDay,
  monthCells,
  nextHourDatetimeLocal,
  shiftMonth,
  unixToDatetimeLocal,
} from './siteEvents';

describe('siteEvents', () => {
  it('round-trips datetime-local through unix seconds', () => {
    const unix = datetimeLocalToUnix('2026-09-15T15:30');
    expect(unix).toBeGreaterThan(0);
    const local = unixToDatetimeLocal(unix);
    expect(local).toMatch(/^2026-09-15T15:30$/);
  });

  it('builds a Monday-first month grid', () => {
    const cells = monthCells(2026, 8);
    expect(cells.length % 7).toBe(0);
    expect(cells.some((cell) => cell.inMonth && cell.day === 1)).toBe(true);
    expect(cells.filter((cell) => cell.inMonth)).toHaveLength(30);
  });

  it('marks multi-day events on each touched day', () => {
    const event = {
      startsAt: Math.floor(new Date(2026, 8, 15, 10, 0, 0).getTime() / 1000),
      endsAt: Math.floor(new Date(2026, 8, 16, 12, 0, 0).getTime() / 1000),
    };
    expect(eventTouchesDay(event, 2026, 8, 15)).toBe(true);
    expect(eventTouchesDay(event, 2026, 8, 16)).toBe(true);
    expect(eventTouchesDay(event, 2026, 8, 17)).toBe(false);
  });

  it('shifts months across year boundaries', () => {
    expect(shiftMonth(2026, 0, -1)).toEqual({ year: 2025, monthIndex: 11 });
    expect(nextHourDatetimeLocal(new Date('2026-09-15T10:20:00')).endsWith(':00')).toBe(true);
  });
});
