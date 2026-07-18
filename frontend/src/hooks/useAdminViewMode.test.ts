// frontend/src/hooks/useAdminViewMode.test.ts
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useAdminViewMode } from './useAdminViewMode';

describe('useAdminViewMode', () => {
  beforeEach(() => {
    const store = new Map<string, string>();
    vi.stubGlobal('localStorage', {
      getItem: (key: string) => store.get(key) ?? null,
      setItem: (key: string, value: string) => {
        store.set(key, value);
      },
      removeItem: (key: string) => {
        store.delete(key);
      },
      clear: () => {
        store.clear();
      },
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('persists view mode per section', () => {
    const { result, rerender } = renderHook(() => useAdminViewMode('media', 'preview'));

    expect(result.current.mode).toBe('preview');

    act(() => {
      result.current.setMode('list');
    });

    expect(result.current.mode).toBe('list');
    expect(window.localStorage.getItem('paginium.admin.viewMode.media')).toBe('list');

    rerender();
    expect(result.current.mode).toBe('list');
  });
});
