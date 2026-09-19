import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import { GitPublishPanel } from './GitPublishPanel';
import { renderWithProviders } from '../../test/renderWithProviders';
import { fastUser } from '../../test/userEvent';

const mocks = vi.hoisted(() => ({
  status: vi.fn(),
  preview: vi.fn(),
  publish: vi.fn(),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
  },
}));

vi.mock('../../api/git', () => ({
  gitApi: {
    status: mocks.status,
    preview: mocks.preview,
    publish: mocks.publish,
    retry: vi.fn(),
  },
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

describe('GitPublishPanel', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.status.mockResolvedValue({
      success: true,
      data: {
        enabled: true,
        strategy: 'queued',
        publisher: 'github_api',
        pendingCount: 1,
        pending: [{ id: 'q1', resourcePath: 'pages/home.json', status: 'pending_publish' }],
        publisherStatus: { publisher: 'github_api', repositoryConfigured: true },
      },
    });
    mocks.preview.mockResolvedValue({
      success: true,
      data: {
        strategy: 'queued',
        pathCount: 1,
        paths: ['pages/home.json'],
        message: 'content: publish 1 change(s)',
      },
    });
    mocks.publish.mockResolvedValue({ success: true, data: { success: true, state: 'pushed' } });
  });

  it('shows queued paths and publishes a release', async () => {
    renderWithProviders(<GitPublishPanel />, { locale: 'en' });

    expect(await screen.findByTestId('git-publish-panel')).toBeInTheDocument();
    expect(screen.getByText('pages/home.json')).toBeInTheDocument();

    await fastUser.click(screen.getByRole('button', { name: 'Publish release' }));
    await waitFor(() => {
      expect(screen.getByRole('button', { name: 'Create release commit' })).toBeInTheDocument();
    });
    await fastUser.click(screen.getByRole('button', { name: 'Create release commit' }));

    await waitFor(() => {
      expect(mocks.publish).toHaveBeenCalled();
    });
  });

  it('hides the panel when Git publish is disabled', async () => {
    mocks.status.mockResolvedValue({
      success: true,
      data: {
        enabled: false,
        strategy: 'disabled',
        publisher: 'local',
        pendingCount: 0,
        pending: [],
        publisherStatus: {},
      },
    });

    const { container } = renderWithProviders(<GitPublishPanel />, { locale: 'en' });
    await waitFor(() => {
      expect(mocks.status).toHaveBeenCalled();
    });
    expect(container.querySelector('[data-testid="git-publish-panel"]')).toBeNull();
  });
});
