import { describe, expect, it, beforeEach, afterEach } from 'vitest';
import {
  clearSystemUpdateCheckCache,
  clearSystemUpdateSessionAutoCheck,
  hasSystemUpdateSessionAutoCheck,
  isSystemUpdateCacheFresh,
  markSystemUpdateSessionAutoCheck,
  normalizeRemoteCheckIntervalHours,
  readSystemUpdateCheckCache,
  writeSystemUpdateCheckCache,
} from './systemUpdateCheckCache';

describe('systemUpdateCheckCache', () => {
  beforeEach(() => {
    clearSystemUpdateCheckCache();
    clearSystemUpdateSessionAutoCheck();
  });

  afterEach(() => {
    clearSystemUpdateCheckCache();
    clearSystemUpdateSessionAutoCheck();
  });

  it('round-trips cache entry', () => {
    const entry = {
      checkedAt: Date.now(),
      check: {
        git: { available: true },
        remote: {},
        update: { status: 'current' as const, current_version: '2.1.0-beta.90' },
      },
      status: { app_version: '2.1.0-beta.90', demo_mode: false, git: { available: true }, config: {}, job_registered: true, recent_runs: [] },
    };
    writeSystemUpdateCheckCache(entry);
    const read = readSystemUpdateCheckCache();
    expect(read?.check.update?.status).toBe('current');
    expect(read?.status?.app_version).toBe('2.1.0-beta.90');
  });

  it('treats interval 0 as always fresh for auto policy', () => {
    const old = Date.now() - 48 * 60 * 60 * 1000;
    expect(isSystemUpdateCacheFresh(old, 0)).toBe(true);
  });

  it('detects stale cache by interval hours', () => {
    const recent = Date.now() - 30 * 60 * 1000;
    const old = Date.now() - 25 * 60 * 60 * 1000;
    expect(isSystemUpdateCacheFresh(recent, 24)).toBe(true);
    expect(isSystemUpdateCacheFresh(old, 24)).toBe(false);
  });

  it('tracks one-shot auto check per session', () => {
    expect(hasSystemUpdateSessionAutoCheck()).toBe(false);
    markSystemUpdateSessionAutoCheck();
    expect(hasSystemUpdateSessionAutoCheck()).toBe(true);
    clearSystemUpdateSessionAutoCheck();
    expect(hasSystemUpdateSessionAutoCheck()).toBe(false);
  });

  it('normalizes interval hours', () => {
    expect(normalizeRemoteCheckIntervalHours(undefined)).toBe(0);
    expect(normalizeRemoteCheckIntervalHours(0)).toBe(0);
    expect(normalizeRemoteCheckIntervalHours(999)).toBe(168);
  });
});
