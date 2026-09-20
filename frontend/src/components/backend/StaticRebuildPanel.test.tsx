import { describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { StaticRebuildPanel } from './StaticRebuildPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { staticSiteApi } from '../../api/staticSite';

const toast = {
  success: vi.fn(),
  error: vi.fn(),
  warning: vi.fn(),
  info: vi.fn(),
};

vi.mock('../../hooks/useToast', () => ({
  useToast: () => toast,
}));

vi.mock('../../hooks/useConfirm', () => ({
  useConfirm: () => async () => true,
}));

vi.mock('../../api/staticSite', () => ({
  staticSiteApi: {
    status: vi.fn(),
    rebuildAll: vi.fn(),
  },
}));

describe('StaticRebuildPanel', () => {
  it('loads status and rebuilds the static tree', async () => {
    vi.mocked(staticSiteApi.status).mockResolvedValue({
      success: true,
      data: {
        renderMode: 'hybrid',
        autoRebuild: true,
        generatedAt: 1_700_000_000,
        pageCount: 2,
        articleCount: 1,
        writable: true,
        tree: 'static',
        publicServe: true,
        publicPrefix: '/static-html',
      },
    });
    vi.mocked(staticSiteApi.rebuildAll).mockResolvedValue({
      success: true,
      data: { success: true, message: 'Static tree rebuilt', written: 3, removed: 0 },
    });

    renderWithProviders(<StaticRebuildPanel />, { locale: 'en' });

    await waitFor(() => {
      expect(screen.getByTestId('static-rebuild-panel')).toBeInTheDocument();
    });
    expect(screen.getByText('hybrid')).toBeInTheDocument();
    expect(screen.getByText(/\/static-html\/pages/)).toBeInTheDocument();

    fireEvent.click(screen.getByRole('button', { name: /rebuild static tree/i }));

    await waitFor(() => {
      expect(staticSiteApi.rebuildAll).toHaveBeenCalled();
    });
    expect(toast.success).toHaveBeenCalled();
  });
});
