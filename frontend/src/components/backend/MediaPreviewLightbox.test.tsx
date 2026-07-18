// frontend/src/components/backend/MediaPreviewLightbox.test.tsx
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { MediaPreviewLightbox } from './MediaPreviewLightbox';
import type { MediaFile } from '../../api/media';

vi.mock('../../api/media', () => ({
  resolveAdminMediaPreviewUrl: (path: string) => `/api/media/file/${path}`,
  resolvePublicMediaUrl: (url: string) => url,
  formatMediaSize: (bytes: number) => `${bytes} B`,
}));

const sampleFile: MediaFile = {
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

describe('MediaPreviewLightbox', () => {
  beforeEach(() => {
    vi.stubGlobal(
      'Image',
      class {
        naturalWidth = 1200;
        naturalHeight = 800;
        onload: (() => void) | null = null;
        set src(_value: string) {
          setTimeout(() => this.onload?.(), 0);
        }
      }
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders nothing when file is null', () => {
    const { container } = render(
      <MediaPreviewLightbox
        file={null}
        mode="fit"
        onClose={vi.fn()}
        onModeChange={vi.fn()}
      />
    );

    expect(container.firstChild).toBeNull();
  });

  it('opens dialog with file metadata and preview image', async () => {
    render(
      <MediaPreviewLightbox
        file={sampleFile}
        mode="fit"
        onClose={vi.fn()}
        onModeChange={vi.fn()}
      />
    );

    expect(screen.getByRole('dialog')).toBeInTheDocument();
    expect(screen.getByText('Hero')).toBeInTheDocument();
    const img = screen.getByAltText('Hero banner') as HTMLImageElement;
    Object.defineProperty(img, 'naturalWidth', { value: 1200 });
    Object.defineProperty(img, 'naturalHeight', { value: 800 });
    fireEvent.load(img);

    expect(await screen.findByText(/1200×800px/)).toBeInTheDocument();
  });

  it('calls onClose when backdrop is clicked', () => {
    const onClose = vi.fn();
    render(
      <MediaPreviewLightbox
        file={sampleFile}
        mode="fit"
        onClose={onClose}
        onModeChange={vi.fn()}
      />
    );

    fireEvent.click(screen.getByRole('dialog'));
    expect(onClose).toHaveBeenCalled();
  });

  it('switches to native mode', () => {
    const onModeChange = vi.fn();
    render(
      <MediaPreviewLightbox
        file={sampleFile}
        mode="fit"
        onClose={vi.fn()}
        onModeChange={onModeChange}
      />
    );

    fireEvent.click(screen.getByRole('button', { name: /1:1/i }));
    expect(onModeChange).toHaveBeenCalledWith('native');
  });
});
