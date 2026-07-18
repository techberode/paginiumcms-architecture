import { describe, expect, it } from 'vitest';
import {
  pickContentImageRaw,
  resolveContentImageUrl,
  resolveContentPreviewImage,
} from './contentPreviewImage';

describe('contentPreviewImage', () => {
  it('prefers featuredImage then seoImage', () => {
    expect(
      pickContentImageRaw({
        featuredImage: '',
        ogImage: '/storage/app/content/media/a.jpg',
        frontMatter: { seoImage: '/storage/b.jpg' },
      })
    ).toBe('/storage/app/content/media/a.jpg');

    expect(
      pickContentImageRaw({
        featuredImage: '',
        ogImage: '/storage/app/content/media/og.jpg',
      })
    ).toBe('/storage/app/content/media/og.jpg');

    expect(
      pickContentImageRaw({
        frontMatter: { seoImage: '/storage/app/content/media/seo.jpg' },
      })
    ).toBe('/storage/app/content/media/seo.jpg');
  });

  it('normalizes storage and media paths', () => {
    expect(resolveContentImageUrl('/storage/app/content/media/x.png')).toBe(
      '/storage/app/content/media/x.png'
    );
    expect(resolveContentImageUrl('media/x.png')).toBe('/storage/app/content/media/x.png');
  });

  it('resolveContentPreviewImage combines pick and resolve', () => {
    expect(
      resolveContentPreviewImage({
        ogImage: 'media/hero.jpg',
      })
    ).toBe('/storage/app/content/media/hero.jpg');
  });
});
