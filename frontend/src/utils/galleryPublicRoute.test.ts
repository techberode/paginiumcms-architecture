import { describe, it, expect } from 'vitest';
import {
  galleryPublicSlug,
  isGalleryPublicPath,
  normalizeGalleryPublicPath,
} from './galleryPublicRoute';

describe('galleryPublicRoute', () => {
  it('normalizes paths to a single segment', () => {
    expect(normalizeGalleryPublicPath('/features')).toBe('/features');
    expect(normalizeGalleryPublicPath('features')).toBe('/features');
    expect(normalizeGalleryPublicPath('/funkcie/')).toBe('/funkcie');
    expect(normalizeGalleryPublicPath('/a/b')).toBe('/a');
    expect(normalizeGalleryPublicPath('')).toBe('/features');
    expect(normalizeGalleryPublicPath(null)).toBe('/features');
  });

  it('extracts slug and matches pathname', () => {
    expect(galleryPublicSlug('/funkcie')).toBe('funkcie');
    expect(isGalleryPublicPath('/funkcie', '/funkcie/')).toBe(true);
    expect(isGalleryPublicPath('/features', '/funkcie')).toBe(false);
  });
});
