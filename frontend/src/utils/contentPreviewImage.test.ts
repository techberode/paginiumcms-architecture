import { describe, expect, it } from 'vitest';
import {
  buildResponsiveSrcSet,
  pickContentImageRaw,
  resolveContentImageUrl,
  resolveContentPreviewImage,
  toCssBackgroundImage,
} from './contentPreviewImage';

describe('contentPreviewImage', () => {
  it('ignores alt-like strings that are not image paths', () => {
    expect(
      pickContentImageRaw({
        featuredImage: 'Diagram architektúry hybridného renderu',
        ogImage: '',
        frontMatter: { seoImage: 'media/hero.jpg' },
      })
    ).toBe('media/hero.jpg');

    expect(
      pickContentImageRaw({
        featuredImage: '404',
        ogImage: '/storage/app/content/media/ok.png',
      })
    ).toBe('/storage/app/content/media/ok.png');
  });

  it('prefers seoImage over stale featuredImage paths', () => {
    expect(
      pickContentImageRaw({
        featuredImage: '/storage/app/content/media/stale.jpg',
        ogImage: '/storage/app/content/media/a.jpg',
        frontMatter: { seoImage: '/storage/b.jpg' },
      })
    ).toBe('/storage/b.jpg');

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

  it('rewrites admin media file URLs to public storage', () => {
    expect(
      resolveContentImageUrl('/api/media/file/media%2Fhero.jpg')
    ).toBe('/storage/app/content/media/hero.jpg');
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

  it('builds a quoted CSS url() and rejects non-http relative schemes', () => {
    expect(toCssBackgroundImage('/storage/app/content/media/hero.jpg?w=960')).toBe(
      'url("/storage/app/content/media/hero.jpg?w=960")'
    );
    expect(toCssBackgroundImage('javascript:alert(1)')).toBe('');
    expect(toCssBackgroundImage('')).toBe('');
  });

  it('appends thumbnail width query when requested', () => {
    expect(
      resolveContentPreviewImage(
        {
          ogImage: '/storage/app/content/media/hero.jpg',
        },
        480
      )
    ).toBe('/storage/app/content/media/hero.jpg?w=480');
  });

  it('builds a responsive srcset for storage URLs', () => {
    expect(buildResponsiveSrcSet('/storage/app/content/media/hero.jpg', [480, 960])).toBe(
      '/storage/app/content/media/hero.jpg?w=480 480w, /storage/app/content/media/hero.jpg?w=960 960w'
    );
    expect(buildResponsiveSrcSet('https://cdn.example/hero.jpg', [480, 960])).toBe('');
  });
});
