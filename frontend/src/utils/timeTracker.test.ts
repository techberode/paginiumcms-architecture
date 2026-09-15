import { describe, expect, it } from 'vitest';
import { elapsedSeconds, formatDuration, startOfLocalWeek } from './timeTracker';

describe('timeTracker', () => {
  it('formats hours minutes seconds', () => {
    expect(formatDuration(0)).toBe('00:00:00');
    expect(formatDuration(75)).toBe('00:01:15');
    expect(formatDuration(3661)).toBe('01:01:01');
  });

  it('computes live elapsed seconds', () => {
    expect(elapsedSeconds(100, null, 160)).toBe(60);
    expect(elapsedSeconds(100, 140, 999)).toBe(40);
  });

  it('uses Monday as the start of the week', () => {
    const wednesday = new Date(2026, 8, 16, 12, 0, 0);
    const monday = startOfLocalWeek(wednesday);
    const mondayDate = new Date(monday * 1000);
    expect(mondayDate.getDay()).toBe(1);
    expect(mondayDate.getDate()).toBe(14);
  });
});
