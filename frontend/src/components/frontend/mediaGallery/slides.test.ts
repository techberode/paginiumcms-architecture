import { describe, expect, it } from 'vitest';
import type { GalleryItem } from '../../../api/gallery';
import { galleryItemsToMediaSlides, proseLightboxSlidesToMedia } from './slides';

describe('mediaGallery slides', () => {
  it('maps prose slides with caption', () => {
    const media = proseLightboxSlidesToMedia([
      { type: 'image', src: '/storage/a.png', alt: 'A', caption: 'Caption', excludeFromSlideshow: false },
    ]);
    expect(media[0]).toMatchObject({
      type: 'image',
      src: '/storage/a.png',
      description: 'Caption',
    });
  });

  it('falls back prose description to alt when caption empty', () => {
    const media = proseLightboxSlidesToMedia([
      { type: 'image', src: '/storage/a.png', alt: 'Alt only', caption: '', excludeFromSlideshow: true },
    ]);
    expect(media[0]?.excludeFromSlideshow).toBe(true);
  });

  it('maps prose slideshow flag when false', () => {
    const media = proseLightboxSlidesToMedia([
      { type: 'image', src: '/storage/a.png', alt: 'Alt only', caption: '', excludeFromSlideshow: false },
    ]);
    expect(media[0]?.description).toBe('Alt only');
  });

  it('maps gallery items with optional tag and link', () => {
    const item: GalleryItem = {
      id: '1',
      title: 'Shot',
      description: 'Desc',
      mediaPath: '/storage/g.png',
      featureTag: 'studio',
      linkUrl: 'https://example.com',
      sortOrder: 0,
      status: 'published',
      createdAt: '2026-01-01T00:00:00Z',
      updatedAt: '2026-01-01T00:00:00Z',
    };
    const media = galleryItemsToMediaSlides([item]);
    expect(media[0]).toMatchObject({
      src: '/storage/g.png',
      title: 'Shot',
      featureTag: 'studio',
      linkUrl: 'https://example.com',
    });
  });

  it('hides feature tag when showFeatureTags is false', () => {
    const item: GalleryItem = {
      id: '1',
      title: 'Shot',
      description: '',
      mediaPath: '/storage/g.png',
      featureTag: 'studio',
      linkUrl: '',
      sortOrder: 0,
      status: 'published',
      createdAt: '2026-01-01T00:00:00Z',
      updatedAt: '2026-01-01T00:00:00Z',
    };
    const media = galleryItemsToMediaSlides([item], { showFeatureTags: false });
    expect(media[0]?.featureTag).toBeUndefined();
  });

  it('maps prose video slides with mime', () => {
    const media = proseLightboxSlidesToMedia([
      {
        type: 'video',
        src: '/storage/media/clip.mp4',
        alt: 'Clip',
        caption: '',
        excludeFromSlideshow: false,
      },
    ]);
    expect(media[0]).toMatchObject({
      type: 'video',
      videoMime: 'video/mp4',
    });
  });

  it('maps gallery video items by path extension', () => {
    const item: GalleryItem = {
      id: '2',
      title: 'Reel',
      description: '',
      mediaPath: '/storage/media/reel.webm',
      featureTag: '',
      linkUrl: '',
      sortOrder: 0,
      status: 'published',
      createdAt: '2026-01-01T00:00:00Z',
      updatedAt: '2026-01-01T00:00:00Z',
    };
    const media = galleryItemsToMediaSlides([item]);
    expect(media[0]?.type).toBe('video');
    expect(media[0]?.videoMime).toBe('video/webm');
  });
});
