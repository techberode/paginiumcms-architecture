// frontend/src/api/media.test.ts
import { describe, it, expect } from 'vitest';
import { formatMediaSize, isImageMedia, isPreviewableMedia, resolveAdminMediaPreviewUrl, resolveMediaUrl } from './media';
import type { MediaFile } from './media';

describe('media API helpers', () => {
  it('resolveMediaUrl uses same-origin for storage paths', () => {
    expect(resolveMediaUrl('/storage/app/content/media/x.png')).toBe(
      '/storage/app/content/media/x.png'
    );
  });

  it('resolveAdminMediaPreviewUrl builds authenticated file route', () => {
    expect(resolveAdminMediaPreviewUrl('media/x.png')).toBe('/api/media/file/media/x.png');
  });

  it('resolveMediaUrl leaves absolute URLs unchanged', () => {
    const url = 'https://cdn.example.com/a.png';
    expect(resolveMediaUrl(url)).toBe(url);
  });

  it('formatMediaSize renders human-readable sizes', () => {
    expect(formatMediaSize(512)).toBe('512 B');
    expect(formatMediaSize(2048)).toBe('2.0 KB');
    expect(formatMediaSize(2 * 1024 * 1024)).toBe('2.0 MB');
  });

  it('isImageMedia detects image mime types', () => {
    const image: MediaFile = {
      id: '1',
      path: 'media/x.png',
      fileName: 'x.png',
      url: '/storage/x.png',
      sizeBytes: 100,
      mimeType: 'image/png',
      uploadedAt: 0,
      altText: '',
      folder: '',
      title: '',
    };
    const pdf: MediaFile = { ...image, mimeType: 'application/pdf' };

    expect(isImageMedia(image)).toBe(true);
    expect(isImageMedia(pdf)).toBe(false);
    expect(isPreviewableMedia(image, ['image/png'])).toBe(true);
    expect(isPreviewableMedia(pdf, ['image/png'])).toBe(false);
  });
});
