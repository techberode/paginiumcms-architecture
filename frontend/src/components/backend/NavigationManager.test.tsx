// frontend/src/components/backend/NavigationManager.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { NavigationManager } from './NavigationManager';

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

  it('loads navigation once and renders items', async () => {
    render(<NavigationManager />);

    await waitFor(() => {
      expect(mocks.getNavigation).toHaveBeenCalledTimes(1);
    });

    expect(screen.getByText('Home')).toBeInTheDocument();
    expect(screen.getByText('/blog')).toBeInTheDocument();
  });

  it('does not refetch in a loop after initial load', async () => {
    render(<NavigationManager />);

    await waitFor(() => {
      expect(mocks.getNavigation).toHaveBeenCalled();
    });

    await new Promise((resolve) => setTimeout(resolve, 100));
    expect(mocks.getNavigation).toHaveBeenCalledTimes(1);
  });
});
