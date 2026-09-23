import { describe, expect, it } from 'vitest';
import {
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
});
