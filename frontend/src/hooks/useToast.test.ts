// frontend/src/hooks/useToast.test.ts
import { describe, it, expect, vi } from 'vitest';
import { renderHook } from '@testing-library/react';
import { useToast } from './useToast';

const stableFns = {
  success: vi.fn(),
  error: vi.fn(),
  warning: vi.fn(),
  info: vi.fn(),
  addNotification: vi.fn(),
  removeNotification: vi.fn(),
  clearNotifications: vi.fn(),
  notifications: [] as unknown[],
  toastEnabled: true,
  toastPosition: 'top-right',
  toastDebugMode: false,
};

vi.mock('../context/NotificationContext', () => ({
  useNotification: () => stableFns,
}));

describe('useToast', () => {
  it('returns stable object reference across rerenders', () => {
    const { result, rerender } = renderHook(() => useToast());
    const first = result.current;
    rerender();
    const second = result.current;

    expect(first).toBe(second);
    expect(first.error).toBe(stableFns.error);
  });
});
