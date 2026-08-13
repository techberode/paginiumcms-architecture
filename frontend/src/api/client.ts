// frontend/src/api/client.ts
import axios, { AxiosHeaders, AxiosInstance, AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';
import { debugLogApi } from '../utils/debugLog';
import { resolveApiBaseUrl } from '../utils/apiBaseUrl';

export interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  error?: string;
  message?: string;
  status?: number;
  // Priame polia z backendu (nie vždy v data) – spätne kompatibilné.
  user?: unknown;
  token?: string;
  requires_two_factor?: boolean;
  requires_otp?: boolean;
  challenge_id?: string;
  expires_at?: number;
  debug_code?: string;
  enabled?: boolean;
  verified?: boolean;
  setup_pending?: boolean;
  secret?: string;
  qr_code?: string;
  provisioning_uri?: string;
  // Doplnkové pole prítomné pri 409 konflikte zámku (viď locking API).
  lock?: unknown;
  // Doplnkové pole prítomné pri 409 konflikte obsahu (viď content conflict).
  conflict?: unknown;
  // Doplnkové pole prítomné pri 422 validačnej chybe (jednotný Error Handler, Iterácia 4).
  errors?: Record<string, string[]>;
  // Stránkovanie (Iterácia 19).
  meta?: PaginationMeta;
}

export interface PaginationMeta {
  page: number;
  per_page: number;
  total: number;
  total_pages: number;
  tags?: string[];
  total_published?: number;
}

export interface PaginatedResponse<T> extends ApiResponse<T[]> {
  meta: PaginationMeta;
}

export interface ApiError {
  success: false;
  error: string;
  status?: number;
}

class ApiClient {
  private client: AxiosInstance;
  private static instance: ApiClient;
  private static authExpiredLastDispatchMs = 0;
  private static readonly AUTH_EXPIRED_DEBOUNCE_MS = 2500;

  private constructor() {
    this.client = axios.create({
      baseURL: resolveApiBaseUrl(),
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      withCredentials: true,
    });

    // Request interceptor – session cookie (withCredentials) + CSRF, bez Bearer tokenu.
    this.client.interceptors.request.use(
      (config) => {
        const csrfToken = this.getCsrfToken();
        if (csrfToken) {
          config.headers['X-CSRF-TOKEN'] = csrfToken;
        }

        if (typeof FormData !== 'undefined' && config.data instanceof FormData) {
          if (config.headers instanceof AxiosHeaders) {
            config.headers.delete('Content-Type');
            config.headers.delete('content-type');
          } else if (config.headers) {
            delete config.headers['Content-Type'];
            delete config.headers['content-type'];
          }
        }

        const url = String(config.url ?? '');
        debugLogApi('request', String(config.method ?? 'get'), url, {
          params: config.params ?? null,
        });

        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor – 401 na verejnom webe nesmie hádzať na /login.
    this.client.interceptors.response.use(
      (response) => {
        const url = String(response.config.url ?? '');
        debugLogApi('response', String(response.config.method ?? 'get'), url, {
          status: response.status,
          success: (response.data as ApiResponse | undefined)?.success ?? null,
        });
        return response;
      },
      async (error: AxiosError) => {
        const url = String(error.config?.url ?? '');
        debugLogApi('error', String(error.config?.method ?? 'get'), url, {
          status: error.response?.status ?? null,
          message: error.message,
          apiError: (error.response?.data as ApiResponse | undefined)?.error ?? null,
        });

        // Self-healing CSRF: server odmietol chýbajúci/neplatný token → raz
        // dofetchni čerstvý token a zopakuj pôvodný request (max 1×).
        const csrfPayload = error.response?.data as (ApiResponse & { code?: string }) | undefined;
        const retryConfig = error.config as (typeof error.config & { _csrfRetried?: boolean }) | undefined;
        if (
          error.response?.status === 403 &&
          csrfPayload?.code === 'csrf_invalid' &&
          retryConfig &&
          !retryConfig._csrfRetried
        ) {
          retryConfig._csrfRetried = true;
          const fresh = await this.refreshCsrfToken();
          if (fresh) {
            retryConfig.headers = retryConfig.headers ?? {};
            (retryConfig.headers as Record<string, string>)['X-CSRF-TOKEN'] = fresh;
            return this.client.request(retryConfig);
          }
        }

        if (error.response?.status === 401) {
          const requestUrl = String(error.config?.url ?? '');
          const payload = error.response?.data as ApiResponse | undefined;

          if (payload?.requires_two_factor === true) {
            window.dispatchEvent(new CustomEvent('paginium:totp-required'));
          } else if (
            !requestUrl.includes('/api/auth/me') &&
            !requestUrl.includes('/api/auth/login') &&
            !requestUrl.includes('/api/auth/2fa/') &&
            !requestUrl.includes('/api/settings/public') &&
            !requestUrl.includes('/api/locks') &&
            !requestUrl.includes('/api/drafts')
          ) {
            const now = Date.now();
            if (now - ApiClient.authExpiredLastDispatchMs >= ApiClient.AUTH_EXPIRED_DEBOUNCE_MS) {
              ApiClient.authExpiredLastDispatchMs = now;
              window.dispatchEvent(new CustomEvent('paginium:auth-expired'));
            }
          }
        }
        return Promise.reject(error);
      }
    );
  }

  public static getInstance(): ApiClient {
    if (!ApiClient.instance) {
      ApiClient.instance = new ApiClient();
    }
    return ApiClient.instance;
  }

  private getCsrfToken(): string | null {
    return localStorage.getItem('csrf_token') || null;
  }

  public setAuthToken(_token: string): void {
    // Session auth cez HttpOnly cookie – Bearer token sa nepoužíva (Iterácia 5).
  }

  public clearAuthToken(): void {
    // Zachované pre kompatibilitu volaní; session sa ruší cez /api/auth/logout.
  }

  public setCsrfToken(token: string): void {
    localStorage.setItem('csrf_token', token);
  }

  /**
   * Vyžiada nový CSRF token zo servera a uloží ho.
   * GET je bezpečná metóda → nespustí CSRF ochranu (žiadna slučka).
   */
  public async refreshCsrfToken(): Promise<string | null> {
    try {
      const res = await this.client.get('/api/auth/csrf-token', {
        params: { key: 'default' },
      });
      const data = res.data as ApiResponse<{ token?: string }> | undefined;
      const token = data?.token ?? data?.data?.token ?? null;
      if (token) {
        this.setCsrfToken(token);
      }
      return token;
    } catch {
      return null;
    }
  }

  // GET request
  public async get<T = unknown>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.get(url, config);
      return response.data;
    } catch (error) {
      return this.handleError<T>(error);
    }
  }

  // POST request
  public async post<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.post(url, data, config);
      return response.data;
    } catch (error) {
      return this.handleError<T>(error);
    }
  }

  // PUT request
  public async put<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.put(url, data, config);
      return response.data;
    } catch (error) {
      return this.handleError<T>(error);
    }
  }

  // DELETE request
  public async delete<T = unknown>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.delete(url, config);
      return response.data;
    } catch (error) {
      return this.handleError<T>(error);
    }
  }

  // PATCH request
  public async patch<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.patch(url, data, config);
      return response.data;
    } catch (error) {
      return this.handleError<T>(error);
    }
  }

  private handleError<T = unknown>(error: unknown): ApiResponse<T> {
    if (axios.isAxiosError(error)) {
      const response = (error.response?.data ?? {}) as ApiResponse<T>;
      // Zachováme aj prípadné doplnkové polia z chybovej odpovede (napr. `lock` pri 409 konflikte),
      // aby ich volajúci (napr. locking API) vedel spracovať. Rozšírenie je spätne kompatibilné.
      return {
        ...response,
        success: false,
        error: response?.error || error.message,
        message: response?.message || 'An error occurred',
        status: error.response?.status,
      };
    }
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Unknown error occurred',
    };
  }
}

export const apiClient = ApiClient.getInstance();
export default apiClient;
