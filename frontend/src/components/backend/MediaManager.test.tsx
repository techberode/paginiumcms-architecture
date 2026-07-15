// frontend/src/components/backend/MediaManager.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MediaManager } from './MediaManager';

const mocks = vi.hoisted(() => ({
  listMedia: vi.fn(),
  uploadMedia: vi.fn(),
  deleteMedia: vi.fn(),
  updateMediaAlt: vi.fn(),
}));

vi.mock('../../api/media', () => ({
  listMedia: mocks.listMedia,
  uploadMedia: mocks.uploadMedia,
  deleteMedia: mocks.deleteMedia,
  updateMediaAlt: mocks.updateMediaAlt,
  resolveMediaUrl: (url: string) => `http://localhost:8080${url}`,
  formatMediaSize: (bytes: number) => `${bytes} B`,
  isImageMedia: (file: { mimeType: string }) => file.mimeType.startsWith('image/'),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  }),
}));

const sampleFile = {
  id: 'media_1',
  path: 'media/media_1_hero.png',
  fileName: 'hero.png',
  url: '/storage/app/content/media/media_1_hero.png',
  sizeBytes: 4096,
  mimeType: 'image/png',
  uploadedAt: 1_700_000_000,
  altText: 'Hero banner',
};

describe('MediaManager', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.listMedia.mockResolvedValue([sampleFile]);
    mocks.uploadMedia.mockResolvedValue({ ok: true, media: sampleFile });
    mocks.deleteMedia.mockResolvedValue(true);
    mocks.updateMediaAlt.mockResolvedValue(true);
    vi.stubGlobal('confirm', vi.fn(() => true));
  });

  it('renders media grid after load', async () => {
    render(<MediaManager />);

    await waitFor(() => {
      expect(mocks.listMedia).toHaveBeenCalled();
    });

    expect(await screen.findByText('hero.png')).toBeInTheDocument();
    expect(screen.getByText(/Alt: Hero banner/)).toBeInTheDocument();
  });

  it('shows empty state when no files', async () => {
    mocks.listMedia.mockResolvedValue([]);
    render(<MediaManager />);

    expect(await screen.findByText('No media files found.')).toBeInTheDocument();
  });

  it('filters items by search query', async () => {
    mocks.listMedia.mockResolvedValue([
      sampleFile,
      { ...sampleFile, id: 'media_2', fileName: 'logo.svg', altText: '' },
    ]);

    render(<MediaManager />);
    expect(await screen.findByText('hero.png')).toBeInTheDocument();

    await userEvent.type(screen.getByPlaceholderText(/Search by name/), 'logo');
    await waitFor(() => {
      expect(screen.queryByText('hero.png')).not.toBeInTheDocument();
      expect(screen.getByText('logo.svg')).toBeInTheDocument();
    });
  });
});
