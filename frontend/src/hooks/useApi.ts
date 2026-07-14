// frontend/src/hooks/useApi.ts
import { useState, useCallback } from 'react';
import apiClient, { ApiResponse } from '../api/client';

export function useApi<T = any>() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [data, setData] = useState<T | null>(null);

  const request = useCallback(
    async <R = T>(
      method: 'get' | 'post' | 'put' | 'delete' | 'patch',
      url: string,
      payload?: any,
      config?: any
    ): Promise<ApiResponse<R>> => {
      setLoading(true);
      setError(null);
      try {
        let response: ApiResponse<R>;
        switch (method) {
          case 'get':
            response = await apiClient.get<R>(url, config);
            break;
          case 'post':
            response = await apiClient.post<R>(url, payload, config);
            break;
          case 'put':
            response = await apiClient.put<R>(url, payload, config);
            break;
          case 'delete':
            response = await apiClient.delete<R>(url, config);
            break;
          case 'patch':
            response = await apiClient.patch<R>(url, payload, config);
            break;
          default:
            throw new Error(`Unsupported method: ${method}`);
        }
        if (response.success) {
          setData(response.data as T);
        } else {
          setError(response.error || 'An error occurred');
        }
        return response;
      } catch (err: any) {
        const errorMessage = err.message || 'An error occurred';
        setError(errorMessage);
        return {
          success: false,
          error: errorMessage,
        };
      } finally {
        setLoading(false);
      }
    },
    []
  );

  const get = useCallback(
    <R = T>(url: string, config?: any): Promise<ApiResponse<R>> => {
      return request<R>('get', url, undefined, config);
    },
    [request]
  );

  const post = useCallback(
    <R = T>(url: string, data?: any, config?: any): Promise<ApiResponse<R>> => {
      return request<R>('post', url, data, config);
    },
    [request]
  );

  const put = useCallback(
    <R = T>(url: string, data?: any, config?: any): Promise<ApiResponse<R>> => {
      return request<R>('put', url, data, config);
    },
    [request]
  );

  const del = useCallback(
    <R = T>(url: string, config?: any): Promise<ApiResponse<R>> => {
      return request<R>('delete', url, undefined, config);
    },
    [request]
  );

  const patch = useCallback(
    <R = T>(url: string, data?: any, config?: any): Promise<ApiResponse<R>> => {
      return request<R>('patch', url, data, config);
    },
    [request]
  );

  return {
    loading,
    error,
    data,
    get,
    post,
    put,
    delete: del,
    patch,
    setData,
    setError,
    clear: () => {
      setData(null);
      setError(null);
      setLoading(false);
    },
  };
}

// Špecifické hooky
export const useAuthApi = () => {
  const { loading, error, data, get, post, put, delete: del } = useApi();
  return { loading, error, data, get, post, put, delete: del };
};

export const useContentApi = () => {
  const { loading, error, data, get, post, put, delete: del } = useApi();
  return { loading, error, data, get, post, put, delete: del };
};

export const useCodeEditorApi = () => {
  const { loading, error, data, get, post, put, delete: del } = useApi();
  return { loading, error, data, get, post, put, delete: del };
};

export const useBackupApi = () => {
  const { loading, error, data, get, post, put, delete: del } = useApi();
  return { loading, error, data, get, post, put, delete: del };
};

export const useAuditApi = () => {
  const { loading, error, data, get, post, put, delete: del } = useApi();
  return { loading, error, data, get, post, put, delete: del };
};

export const useHealthApi = () => {
  const { loading, error, data, get } = useApi();
  return { loading, error, data, get };
};

export const useVersionApi = () => {
  const { loading, error, data, get, post, delete: del } = useApi();
  return { loading, error, data, get, post, delete: del };
};
