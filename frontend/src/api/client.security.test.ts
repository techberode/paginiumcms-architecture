import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import type { AxiosError, InternalAxiosRequestConfig } from 'axios';

const axiosState = vi.hoisted(() => {
  let requestInterceptor: ((config: InternalAxiosRequestConfig) => InternalAxiosRequestConfig) | null = null;
  let responseErrorInterceptor: ((error: AxiosError) => unknown) | null = null;

  const instance = {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    patch: vi.fn(),
    interceptors: {
      request: {
        use: vi.fn((onFulfilled: (config: InternalAxiosRequestConfig) => InternalAxiosRequestConfig) => {
          requestInterceptor = onFulfilled;
        }),
      },
      response: {
        use: vi.fn((_onFulfilled: unknown, onRejected: (error: AxiosError) => unknown) => {
          responseErrorInterceptor = onRejected;
        }),
      },
    },
  };

  return {
    instance,
    getRequestInterceptor: () => requestInterceptor,
    getResponseErrorInterceptor: () => responseErrorInterceptor,
    reset: () => {
      requestInterceptor = null;
      responseErrorInterceptor = null;
    },
  };
});

vi.mock('axios', () => ({
  default: {
    create: vi.fn(() => axiosState.instance),
    isAxiosError: (error: unknown) => Boolean((error as AxiosError)?.isAxiosError),
  },
  create: vi.fn(() => axiosState.instance),
  isAxiosError: (error: unknown) => Boolean((error as AxiosError)?.isAxiosError),
}));

vi.mock('../utils/debugLog', () => ({
  debugLogApi: vi.fn(),
}));

vi.mock('../utils/apiBaseUrl', () => ({
  resolveApiBaseUrl: () => 'http://localhost:8080',
}));

async function loadClientModule() {
  vi.resetModules();
  axiosState.reset();
  return import('./client');
}

describe('ApiClient security', () => {
  const location = { pathname: '/dashboard', href: '' };
  const storage = new Map<string, string>();

  beforeEach(() => {
    storage.clear();
    vi.stubGlobal('localStorage', {
      getItem: (key: string) => storage.get(key) ?? null,
      setItem: (key: string, value: string) => {
        storage.set(key, value);
      },
      removeItem: (key: string) => {
        storage.delete(key);
      },
      clear: () => {
        storage.clear();
      },
    });
    location.pathname = '/dashboard';
    location.href = '';
    vi.stubGlobal('location', location);
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.clearAllMocks();
  });

  it('uses session cookies instead of bearer auth', async () => {
    const axios = await import('axios');
    const { default: client } = await loadClientModule();

    expect(axios.default.create).toHaveBeenCalledWith(
      expect.objectContaining({
        withCredentials: true,
        headers: expect.objectContaining({
          Accept: 'application/json',
          'Content-Type': 'application/json',
        }),
      })
    );

    client.setAuthToken('should-not-be-used');
    expect(storage.get('auth_token')).toBeUndefined();
  });

  it('attaches CSRF header when token is stored', async () => {
    storage.set('csrf_token', 'csrf-test-token');
    await loadClientModule();

    const interceptor = axiosState.getRequestInterceptor();
    expect(interceptor).not.toBeNull();

    const config = interceptor!({
      headers: {},
      url: '/api/content/pages',
      method: 'post',
    } as InternalAxiosRequestConfig);

    expect(config.headers['X-CSRF-TOKEN']).toBe('csrf-test-token');
  });

  it('does not attach CSRF header when token is missing', async () => {
    await loadClientModule();

    const interceptor = axiosState.getRequestInterceptor();
    const config = interceptor!({
      headers: {},
      url: '/api/content/pages',
      method: 'get',
    } as InternalAxiosRequestConfig);

    expect(config.headers['X-CSRF-TOKEN']).toBeUndefined();
  });

  it('dispatches auth-expired on 401 for protected API routes without hard redirect', async () => {
    const authExpired = vi.fn();
    window.addEventListener('paginium:auth-expired', authExpired);

    await loadClientModule();
    const interceptor = axiosState.getResponseErrorInterceptor();
    expect(interceptor).not.toBeNull();

    const error = {
      isAxiosError: true,
      message: 'Unauthorized',
      response: { status: 401, data: { success: false, error: 'Unauthorized' } },
      config: { url: '/api/content/pages', method: 'get' },
    } as AxiosError;

    await expect(interceptor!(error)).rejects.toBe(error);
    expect(authExpired).toHaveBeenCalledTimes(1);
    expect(location.href).toBe('');

    window.removeEventListener('paginium:auth-expired', authExpired);
  });

  it('dispatches totp-required when 401 payload requires two-factor login', async () => {
    const totpRequired = vi.fn();
    const authExpired = vi.fn();
    window.addEventListener('paginium:totp-required', totpRequired);
    window.addEventListener('paginium:auth-expired', authExpired);

    await loadClientModule();
    const interceptor = axiosState.getResponseErrorInterceptor();

    const error = {
      isAxiosError: true,
      message: 'Unauthorized',
      response: {
        status: 401,
        data: { success: false, error: 'TOTP required', requires_two_factor: true },
      },
      config: { url: '/api/content/pages', method: 'get' },
    } as AxiosError;

    await expect(interceptor!(error)).rejects.toBe(error);
    expect(totpRequired).toHaveBeenCalledTimes(1);
    expect(authExpired).not.toHaveBeenCalled();
    expect(location.href).toBe('');

    window.removeEventListener('paginium:totp-required', totpRequired);
    window.removeEventListener('paginium:auth-expired', authExpired);
  });

  it('does not dispatch auth events on public auth probe endpoints', async () => {
    const authExpired = vi.fn();
    const totpRequired = vi.fn();
    window.addEventListener('paginium:auth-expired', authExpired);
    window.addEventListener('paginium:totp-required', totpRequired);

    location.href = '';
    location.pathname = '/dashboard';
    await loadClientModule();
    const interceptor = axiosState.getResponseErrorInterceptor();

    const error = {
      isAxiosError: true,
      message: 'Unauthorized',
      response: { status: 401, data: { success: false, error: 'Unauthorized' } },
      config: { url: '/api/auth/me', method: 'get' },
    } as AxiosError;

    await expect(interceptor!(error)).rejects.toBe(error);
    expect(authExpired).not.toHaveBeenCalled();
    expect(totpRequired).not.toHaveBeenCalled();
    expect(location.href).toBe('');

    window.removeEventListener('paginium:auth-expired', authExpired);
    window.removeEventListener('paginium:totp-required', totpRequired);
  });

  it('does not hard-redirect on 401 regardless of frontend pathname', async () => {
    const authExpired = vi.fn();
    window.addEventListener('paginium:auth-expired', authExpired);

    location.pathname = '/blog';
    location.href = '';
    await loadClientModule();
    const interceptor = axiosState.getResponseErrorInterceptor();

    const error = {
      isAxiosError: true,
      message: 'Unauthorized',
      response: { status: 401, data: { success: false, error: 'Unauthorized' } },
      config: { url: '/api/content/pages', method: 'get' },
    } as AxiosError;

    await expect(interceptor!(error)).rejects.toBe(error);
    expect(location.href).toBe('');
    expect(authExpired).toHaveBeenCalledTimes(1);

    window.removeEventListener('paginium:auth-expired', authExpired);
  });
});
