// frontend/src/components/backend/MediaManager.test.tsx
import { describe, it, expect, vi, beforeEach } from 'vitest';
import { screen, within, waitFor } from '@testing-library/react';
import { MediaManager } from './MediaManager';
import { fastUser } from '../../test/userEvent';
import { renderWithRouter } from '../../test/renderWithRouter';

const mocks = vi.hoisted(() => ({
  listMedia: vi.fn(),
  listMediaFolders: vi.fn(),
  importStockImage: vi.fn(),
  listStockImageTopics: vi.fn(),
  listMediaFormats: vi.fn(),
  uploadMedia: vi.fn(),
  deleteMedia: vi.fn(),
  bulkDeleteMedia: vi.fn(),
  createMediaFolder: vi.fn(),
  updateMediaMetadata: vi.fn(),
  getMediaImageInfo: vi.fn(),
  previewOptimizeMedia: vi.fn(),
  applyOptimizeMedia: vi.fn(),
  optimizeMedia: vi.fn(),
  useAdminViewMode: vi.fn(() => ({
    mode: 'preview' as 'list' | 'list-preview' | 'preview',
    setMode: vi.fn(),
  })),
  toast: {
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    toast: {},
  },
}));

vi.mock('../../hooks/useAdminViewMode', () => ({
  useAdminViewMode: mocks.useAdminViewMode,
}));

vi.mock('../../api/media', () => ({
  listMedia: mocks.listMedia,
  listMediaFolders: mocks.listMediaFolders,
  listStockImageTopics: mocks.listStockImageTopics,
  listMediaFormats: mocks.listMediaFormats,
  importStockImage: mocks.importStockImage,
  uploadMedia: mocks.uploadMedia,
  deleteMedia: mocks.deleteMedia,
  bulkDeleteMedia: mocks.bulkDeleteMedia,
  createMediaFolder: mocks.createMediaFolder,
  updateMediaMetadata: mocks.updateMediaMetadata,
  updateMediaAlt: mocks.updateMediaMetadata,
  getMediaImageInfo: mocks.getMediaImageInfo,
  previewOptimizeMedia: mocks.previewOptimizeMedia,
  applyOptimizeMedia: mocks.applyOptimizeMedia,
  optimizeMedia: mocks.optimizeMedia,
  resolveAdminMediaPreviewUrl: (path: string) => `/api/media/file/${path}`,
  resolvePublicMediaUrl: (url: string) => url,
  resolveMediaUrl: (url: string) => url,
  resolveOptimizePreviewUrl: (token: string) => `/api/media/optimize-preview/${token}`,
  formatMediaSize: (bytes: number) => `${bytes} B`,
  scaleMediaDimensions: (
    originalWidth: number,
    originalHeight: number,
    changedAxis: 'width' | 'height',
    newValue: number
  ) => {
    if (changedAxis === 'width') {
      const width = Math.max(1, Math.min(originalWidth, Math.round(newValue)));
      const height = Math.max(1, Math.round((originalHeight * width) / originalWidth));
      return { width, height };
    }
    const height = Math.max(1, Math.min(originalHeight, Math.round(newValue)));
    const width = Math.max(1, Math.round((originalWidth * height) / originalHeight));
    return { width, height };
  },
  isImageMedia: (file: { mimeType: string }) => file.mimeType.startsWith('image/'),
  isPreviewableMedia: (file: { mimeType: string }) => file.mimeType.startsWith('image/'),
  isOptimizableMedia: (file: { mimeType: string }, capabilities?: { available: boolean }) => {
    if (capabilities !== undefined && !capabilities.available) {
      return false;
    }
    return ['image/jpeg', 'image/png', 'image/webp'].includes(file.mimeType.toLowerCase());
  },
}));

vi.mock('../../api/settings', () => ({
  getSettings: vi.fn().mockResolvedValue({
    values: { media: { stockImageTopic: 'tech' } },
  }),
}));

vi.mock('../../hooks/useToast', () => ({
  useToast: () => mocks.toast,
}));

vi.mock('../../hooks/useAdminListPageSize', () => ({
  useAdminListPageSize: () => [20, vi.fn()],
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
  folder: '',
  title: 'Hero',
};

describe('MediaManager', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mocks.useAdminViewMode.mockReturnValue({ mode: 'preview', setMode: vi.fn() });
    mocks.listMedia.mockResolvedValue([sampleFile]);
    mocks.listMediaFolders.mockResolvedValue(['', 'campaigns']);
    mocks.listStockImageTopics.mockResolvedValue([
      { id: 'tech', label: 'Technológie / IT', count: 5 },
      { id: 'food', label: 'Varenie', count: 4 },
    ]);
    mocks.listMediaFormats.mockResolvedValue({
      mimeTypes: ['image/png'],
      extensions: ['png'],
      accept: 'image/png',
      previewableMimeTypes: ['image/png'],
      imageOptimization: {
        available: true,
        jpeg: true,
        png: true,
        webp: true,
      },
    });
    mocks.getMediaImageInfo.mockResolvedValue({
      width: 1920,
      height: 1080,
      mimeType: 'image/png',
      sizeBytes: 4096,
    });
    mocks.previewOptimizeMedia.mockResolvedValue({ ok: false, error: 'Preview not used in test.' });
    mocks.applyOptimizeMedia.mockResolvedValue({ ok: false, error: 'Apply not used in test.' });
    mocks.optimizeMedia.mockResolvedValue({ ok: false, error: 'Optimize not used in test.' });
    mocks.importStockImage.mockResolvedValue({ ok: true, media: sampleFile });
    mocks.uploadMedia.mockResolvedValue({ ok: true, media: sampleFile });
    mocks.deleteMedia.mockResolvedValue(true);
    mocks.bulkDeleteMedia.mockResolvedValue(1);
    mocks.createMediaFolder.mockResolvedValue(true);
    mocks.updateMediaMetadata.mockResolvedValue(true);
    vi.stubGlobal('confirm', vi.fn(() => true));
    vi.stubGlobal('prompt', vi.fn(() => 'new-folder'));
  });

  it('renders media grid after load', async () => {
    renderWithRouter(<MediaManager />);

    expect(await screen.findByRole('button', { name: /Preview hero\.png/i })).toBeInTheDocument();
    expect(screen.getByText(/Alt:\s*Hero banner/i)).toBeInTheDocument();
    expect(mocks.listMedia).toHaveBeenCalled();
    expect(mocks.listMediaFolders).toHaveBeenCalled();
    expect(screen.getByRole('button', { name: /Generovať z knižnice/i })).toBeInTheDocument();
  });

  it('shows empty state when no files', async () => {
    mocks.listMedia.mockResolvedValue([]);
    mocks.listMediaFolders.mockResolvedValue(['']);
    renderWithRouter(<MediaManager />);

    expect(await screen.findByText(/nie sú žiadne súbory/)).toBeInTheDocument();
  });

  it('filters items by search query', async () => {
    mocks.listMedia.mockResolvedValue([
      sampleFile,
      { ...sampleFile, id: 'media_2', fileName: 'logo.svg', altText: '', title: '' },
    ]);

    renderWithRouter(<MediaManager />);
    expect(await screen.findByRole('button', { name: /Preview hero\.png/i })).toBeInTheDocument();

    await fastUser.type(screen.getByPlaceholderText(/Hľadať podľa názvu/), 'logo');

    await waitFor(() => {
      expect(screen.queryByRole('button', { name: /Preview hero\.png/i })).not.toBeInTheDocument();
    });
    expect(screen.getAllByText('logo.svg').length).toBeGreaterThan(0);
  });

  it('opens folder when child folder card is clicked', async () => {
    renderWithRouter(<MediaManager />);
    expect(await screen.findByRole('button', { name: /^campaigns$/i })).toBeInTheDocument();

    await fastUser.click(screen.getByRole('button', { name: /^campaigns$/i }));

    await waitFor(() => {
      expect(mocks.listMedia).toHaveBeenCalledWith(expect.objectContaining({ folder: 'campaigns' }));
    });
  });

  it('saves metadata edits in list view mode via modal', async () => {
    mocks.useAdminViewMode.mockReturnValue({ mode: 'list', setMode: vi.fn() });

    renderWithRouter(<MediaManager />);
    expect(await screen.findByRole('checkbox', { name: /Select hero\.png/i })).toBeInTheDocument();

    await fastUser.click(screen.getByRole('button', { name: /Upraviť metadáta/i }));
    const dialog = await screen.findByRole('dialog');
    expect(dialog).toBeInTheDocument();

    await fastUser.clear(within(dialog).getByLabelText('Titulok'));
    await fastUser.type(within(dialog).getByLabelText('Titulok'), 'Updated title');
    await fastUser.clear(within(dialog).getByLabelText(/Alt text \/ popis/i));
    await fastUser.type(within(dialog).getByLabelText(/Alt text \/ popis/i), 'Updated alt');
    await fastUser.click(screen.getByRole('button', { name: 'Uložiť zmeny' }));

    await waitFor(() => {
      expect(mocks.updateMediaMetadata).toHaveBeenCalledWith('media/media_1_hero.png', {
        altText: 'Updated alt',
        title: 'Updated title',
      });
    });
  });
});
