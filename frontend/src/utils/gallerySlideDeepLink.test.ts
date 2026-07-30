import { describe, expect, it } from 'vitest';
import type { GalleryItem } from '../api/gallery';
import { resolveGallerySlideDeepLink } from './gallerySlideDeepLink';

const items: GalleryItem[] = [
  {
    id: 'gallery_1',
    title: 'Analytics',
    description: '',
    mediaPath: '/storage/media/a.png',
    featureTag: 'analytics',
    sortOrder: 0,
    status: 'published',
    createdAt: '2026-07-30T00:00:00+00:00',
    updatedAt: '2026-07-30T00:00:00+00:00',
  },
  {
    id: 'gallery_2',
    title: 'Newsletter',
    description: '',
    mediaPath: '/storage/media/b.png',
    featureTag: 'newsletter',
    sortOrder: 1,
    status: 'published',
    createdAt: '2026-07-30T00:00:00+00:00',
    updatedAt: '2026-07-30T00:00:00+00:00',
  },
];

describe('resolveGallerySlideDeepLink', () => {
  it('returns empty for blank slide', () => {
    expect(resolveGallerySlideDeepLink(items, null)).toEqual({
      activeTag: null,
      modalIndex: null,
    });
  });

  it('matches by item id', () => {
    expect(resolveGallerySlideDeepLink(items, 'gallery_2')).toEqual({
      activeTag: null,
      modalIndex: 1,
    });
  });

  it('matches by feature tag (case-insensitive)', () => {
    expect(resolveGallerySlideDeepLink(items, 'Analytics')).toEqual({
      activeTag: 'analytics',
      modalIndex: 0,
    });
  });

  it('ignores unknown slide values', () => {
    expect(resolveGallerySlideDeepLink(items, 'missing')).toEqual({
      activeTag: null,
      modalIndex: null,
    });
  });
});
