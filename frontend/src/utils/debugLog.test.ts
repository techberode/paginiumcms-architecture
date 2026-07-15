import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import {
  debugLog,
  debugLogApi,
  debugLogProvider,
  isDebugEnabled,
  initDebugMonitoring,
  logFrontendStartup,
  resetDebugMonitoringForTests,
} from './debugLog';

describe('debugLog', () => {
  beforeEach(() => {
    resetDebugMonitoringForTests();
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true }));
    vi.spyOn(console, 'debug').mockImplementation(() => {});
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });

  it('is enabled in vitest dev mode', () => {
    expect(isDebugEnabled()).toBe(true);
  });

  it('logs startup event to console and API', () => {
    logFrontendStartup();

    expect(console.debug).toHaveBeenCalled();
    expect(fetch).toHaveBeenCalledWith(
      expect.stringContaining('/api/debug/client-event'),
      expect.objectContaining({ method: 'POST' })
    );
  });

  it('debugLogProvider prefixes provider name', () => {
    debugLogProvider('auth', 'test', { ok: true });

    const [, options] = vi.mocked(fetch).mock.calls.at(-1)!;
    const body = JSON.parse(String(options?.body));
    expect(body.event).toBe('provider.auth.test');
  });

  it('debugLogApi marks sensitive auth paths', () => {
    debugLogApi('request', 'post', '/api/auth/login', { data: { password: 'secret' } });

    const [, options] = vi.mocked(fetch).mock.calls.at(-1)!;
    const body = JSON.parse(String(options?.body));
    expect(body.context.sensitive).toBe(true);
    expect(body.context.data).toBeUndefined();
  });

  it('initDebugMonitoring registers only once', () => {
    initDebugMonitoring();
    initDebugMonitoring();
    expect(console.debug).toHaveBeenCalled();
  });
});
