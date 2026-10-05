import { describe, expect, it } from 'vitest';
import {
  collectProseLightboxSlides,
  isProseLightboxDisabled,
  isProseLightboxImageSrc,
  proseImageFullSizeSrc,
} from './proseImageLightbox';

describe('proseImageLightbox', () => {
  it('accepts same-origin storage paths', () => {
    expect(isProseLightboxImageSrc('/storage/media/shot.png')).toBe(true);
  });

  it('rejects external URLs', () => {
    expect(isProseLightboxImageSrc('https://evil.example/x.png')).toBe(false);
  });

  it('strips thumbnail width query for modal', () => {
    expect(proseImageFullSizeSrc('/storage/media/shot.png?w=480')).toBe('/storage/media/shot.png');
  });

  it('respects data-lightbox off', () => {
    const img = document.createElement('img');
    img.setAttribute('data-lightbox', 'off');
    expect(isProseLightboxDisabled(img)).toBe(true);
  });

  it('groups slides by data-gallery', () => {
    const root = document.createElement('div');
    root.innerHTML = `
      <img src="/storage/media/a.png" alt="A" />
      <img src="/storage/media/b.png" alt="B" data-gallery="g1" />
      <img src="/storage/media/c.png" alt="C" data-gallery="g1" />
    `;
    const slidePath = (src: string) => {
      try {
        return new URL(src, 'http://localhost').pathname;
      } catch {
        return src;
      }
    };

    const defaultGroup = collectProseLightboxSlides(root, { galleryGroup: '' });
    expect(defaultGroup.map((s) => slidePath(s.src))).toEqual(['/storage/media/a.png']);

    const g1 = collectProseLightboxSlides(root, { galleryGroup: 'g1' });
    expect(g1.map((s) => slidePath(s.src))).toEqual(['/storage/media/b.png', '/storage/media/c.png']);
  });

  it('marks slideshow exclusion on slides', () => {
    const root = document.createElement('div');
    root.innerHTML = `<img src="/storage/media/a.png" alt="A" data-slideshow="off" />`;
    const slides = collectProseLightboxSlides(root, { galleryGroup: '' });
    expect(slides[0]?.excludeFromSlideshow).toBe(true);
  });
});
