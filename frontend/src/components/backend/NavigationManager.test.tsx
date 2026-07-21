// frontend/src/components/backend/NavigationManager.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { NavigationManager } from './NavigationManager';
import { renderWithRouter } from '../../test/renderWithRouter';

const mocks = vi.hoisted(() => ({
  getNavigation: vi.fn(),
  updateNavigation: vi.fn(),
  error: vi.fn(),
}));

vi.mock('../../api/navigation', () => ({
  getNavigation: mocks.getNavigation,
  updateNavigation: mocks.updateNavigation,
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: mocks.error,
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

describe('NavigationManager', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.getNavigation.mockResolvedValue([
      { id: '1', label: 'Home', path: '/', order: 0, target: '_self' },
      { id: '2', label: 'Blog', path: '/blog', order: 1, target: '_self' },
    ]);
  });

  it('loads navigation once and renders editable items', async () => {
    renderWithRouter(<NavigationManager />);

    expect(await screen.findByDisplayValue('Home')).toBeInTheDocument();
    expect(screen.getByDisplayValue('/blog')).toBeInTheDocument();
    expect(mocks.getNavigation).toHaveBeenCalledTimes(1);
  });

  it('does not refetch in a loop after initial load', async () => {
    renderWithRouter(<NavigationManager />);

    await screen.findByDisplayValue('Home');
    const initialCalls = mocks.getNavigation.mock.calls.length;
    expect(initialCalls).toBe(1);

    await waitFor(
      () => {
        expect(mocks.getNavigation.mock.calls.length).toBe(initialCalls);
      },
      { timeout: 100, interval: 10 }
    );
  });
});
