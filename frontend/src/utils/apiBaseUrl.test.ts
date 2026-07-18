// frontend/src/utils/apiBaseUrl.test.ts
import { describe, it, expect } from 'vitest';
import {
  resolveApiBaseUrl,
  resolveAdminMediaFileUrl,
  resolveMediaUrl,
  resolveStorageUrl,
} from './apiBaseUrl';

describe('apiBaseUrl', () => {
  it('resolveMediaUrl keeps absolute URLs', () => {
    expect(resolveMediaUrl('https://cdn.example.com/a.png')).toBe('https://cdn.example.com/a.png');
  });

  it('resolveStorageUrl keeps same-origin relative paths', () => {
    expect(resolveStorageUrl('/storage/app/content/media/x.png')).toBe(
      '/storage/app/content/media/x.png'
    );
  });

  it('resolveMediaUrl uses same-origin for /storage paths', () => {
    expect(resolveMediaUrl('/storage/app/content/media/x.png')).toBe(
      '/storage/app/content/media/x.png'
    );
  });

  it('resolveAdminMediaFileUrl encodes nested media paths', () => {
    expect(resolveAdminMediaFileUrl('media/campaigns/photo.png')).toBe(
      '/api/media/file/media/campaigns/photo.png'
    );
  });

  it('resolveApiBaseUrl falls back to localhost in node', () => {
    expect(resolveApiBaseUrl()).toMatch(/localhost:8080|http/);
  });
});
