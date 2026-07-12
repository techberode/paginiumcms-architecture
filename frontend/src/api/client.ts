// frontend/src/api/client.ts
import axios, { type AxiosInstance, type AxiosError } from 'axios';
import type { ApiError } from './types';
class ApiClient {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8080',
      headers: {
        'Content-Type': 'application/json',
      },
      withCredentials: true,
    });

    // Request interceptor pre CSRF token
    this.client.interceptors.request.use((config) => {
      const token = localStorage.getItem('csrf_token');
      if (token) {
        config.headers['X-CSRF-Token'] = token;
      }
      return config;
    });

    // Response interceptor pre spracovanie chýb
    this.client.interceptors.response.use(
      (response) => response,
                                          (error: AxiosError<ApiError>) => {
                                            if (error.response?.status === 401) {
                                              localStorage.removeItem('auth_user');
                                              window.dispatchEvent(new CustomEvent('auth:logout'));
                                            }
                                            return Promise.reject(error);
                                          }
    );
  }

  // GET request
  async get<T>(url: string, params?: Record<string, unknown>): Promise<T> {
    const response = await this.client.get<T>(url, { params });
    return response.data;
  }


  // POST request
  async post<T>(url: string, data?: unknown): Promise<T> {
    const response = await this.client.post<T>(url, data);
    return response.data;
  }

  // PUT request
  async put<T>(url: string, data?: unknown): Promise<T> {
    const response = await this.client.put<T>(url, data);
    return response.data;
  }

  // DELETE request
  async delete<T>(url: string): Promise<T> {
    const response = await this.client.delete<T>(url);
    return response.data;
  }

  // CSRF token
  async getCsrfToken(key?: string): Promise<string> {
    try {
      const response = await this.client.get<{ csrf_token: string }>('/auth/csrf-token', {
        params: key ? { key } : undefined,
      });
      const token = response.data.csrf_token;
      localStorage.setItem('csrf_token', token);
      return token;
    } catch {
      // Fallback token pre vývoj
      const fallbackToken = 'dev-' + Math.random().toString(36).substring(2);
      localStorage.setItem('csrf_token', fallbackToken);
      return fallbackToken;
    }
  }
}

// SPRÁVNY EXPORT - na úrovni MODULU (nie vo vnútri triedy!)
export default new ApiClient();
