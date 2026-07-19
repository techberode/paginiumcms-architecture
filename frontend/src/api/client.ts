// frontend/src/api/client.ts
import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse, AxiosError } from 'axios';
import { debugLogApi } from '../utils/debugLog';
import { resolveApiBaseUrl } from '../utils/apiBaseUrl';

export interface ApiResponse<T = any> {
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
      (error: AxiosError) => {
        const url = String(error.config?.url ?? '');
        debugLogApi('error', String(error.config?.method ?? 'get'), url, {
          status: error.response?.status ?? null,
          message: error.message,
          apiError: (error.response?.data as ApiResponse | undefined)?.error ?? null,
        });

        if (error.response?.status === 401) {
          const requestUrl = String(error.config?.url ?? '');
          const skipRedirect =
            requestUrl.includes('/api/auth/me') ||
            requestUrl.includes('/api/settings/public');

          if (!skipRedirect) {
            const adminPrefixes = [
              '/dashboard',
              '/pages',
              '/articles',
              '/media',
              '/navigation',
              '/comments',
              '/messages',
              '/github',
              '/code-editor',
              '/backups',
              '/audit',
              '/notifications',
              '/settings',
              '/users',
            ];
            const onAdminPage = adminPrefixes.some((prefix) =>
              window.location.pathname.startsWith(prefix)
            );
            if (onAdminPage) {
              window.location.href = '/login';
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

  // GET request
  public async get<T = any>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.get(url, config);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // POST request
  public async post<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.post(url, data, config);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // PUT request
  public async put<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.put(url, data, config);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // DELETE request
  public async delete<T = any>(
    url: string,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.delete(url, config);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  // PATCH request
  public async patch<T = any>(
    url: string,
    data?: any,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    try {
      const response: AxiosResponse<ApiResponse<T>> = await this.client.patch(url, data, config);
      return response.data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  private handleError(error: any): ApiResponse {
    if (axios.isAxiosError(error)) {
      const response = error.response?.data as ApiResponse;
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
      error: error?.message || 'Unknown error occurred',
    };
  }
}

export const apiClient = ApiClient.getInstance();
export default apiClient;
