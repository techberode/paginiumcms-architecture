import { describe, expect, it } from 'vitest';
import { mediaGalleryMarkupToSlides, parseMediaGalleryMarkup } from './mediaGalleryMarkup';

describe('mediaGalleryMarkup', () => {
  it('parses server-expanded gallery items', () => {
    const html = `
      <div class="pg-media-gallery-grid">
        <article class="pg-media-gallery-item" data-index="0" data-media-path="media/a.jpg">
          <img class="pg-media-gallery-image" src="/storage/media/a.jpg" alt="A" />
          <span class="pg-media-gallery-item-caption">Caption A</span>
        </article>
      </div>`;

    const items = parseMediaGalleryMarkup(html);
    expect(items).toHaveLength(1);
    expect(items[0]?.mediaPath).toBe('media/a.jpg');
    expect(items[0]?.title).toBe('Caption A');

    const slides = mediaGalleryMarkupToSlides(items);
    expect(slides[0]?.src).toBe('/storage/media/a.jpg');
    expect(slides[0]?.alt).toBe('A');
  });

  it('parses video gallery items from server markup', () => {
    const html = `
      <article class="pg-media-gallery-item" data-index="0" data-media-path="media/clip.mp4"
        data-media-type="video" data-mime-type="video/mp4">
        <video class="pg-media-gallery-video" src="/storage/media/clip.mp4" aria-label="Clip"></video>
      </article>`;

    const items = parseMediaGalleryMarkup(html);
    expect(items[0]?.mediaType).toBe('video');
    const slides = mediaGalleryMarkupToSlides(items);
    expect(slides[0]).toMatchObject({ type: 'video', videoMime: 'video/mp4' });
  });
});
