import { describe, expect, it, vi } from 'vitest';
import { navigateWithViewTransition, supportsViewTransition } from './viewTransitionNavigate';

describe('viewTransitionNavigate', () => {
  it('falls back when view transitions are disabled', () => {
    const navigate = vi.fn();
    navigateWithViewTransition(navigate, '/about', undefined, false);
    expect(navigate).toHaveBeenCalledWith('/about', undefined);
  });

  it('uses startViewTransition when enabled and supported', () => {
    const navigate = vi.fn();
    const start = vi.fn((callback: () => void) => {
      callback();
    });
    vi.stubGlobal('document', {
      startViewTransition: start,
    });

    navigateWithViewTransition(navigate, '/contact', undefined, true);
    expect(start).toHaveBeenCalledTimes(1);
    expect(navigate).toHaveBeenCalledWith('/contact', undefined);

    vi.unstubAllGlobals();
  });

  it('reports support when API exists', () => {
    vi.stubGlobal('document', { startViewTransition: () => undefined });
    expect(supportsViewTransition()).toBe(true);
    vi.unstubAllGlobals();
  });
});
