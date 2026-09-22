import { describe, expect, it } from 'vitest';
import { toneFromConnectionOk, toneFromEnabled, toneFromProbeStatus } from './adminStatusKind';

describe('adminStatusKind', () => {
  it('maps probe statuses', () => {
    expect(toneFromProbeStatus('available')).toBe('available');
    expect(toneFromProbeStatus('failing')).toBe('unavailable');
    expect(toneFromProbeStatus('disabled')).toBe('inactive');
    expect(toneFromProbeStatus(true)).toBe('available');
  });

  it('maps enabled and connection', () => {
    expect(toneFromEnabled(true)).toBe('active');
    expect(toneFromEnabled(false)).toBe('inactive');
    expect(toneFromConnectionOk(true)).toBe('available');
    expect(toneFromConnectionOk(false)).toBe('unavailable');
    expect(toneFromConnectionOk(null)).toBe('neutral');
  });
});
