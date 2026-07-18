// frontend/src/utils/seoHealth.test.ts
import { describe, it, expect } from 'vitest';
import { evaluateContentSeo, evaluateMediaSeo } from './seoHealth';
import type { MediaFile } from '../api/media';

describe('seoHealth', () => {
  const baseMedia: MediaFile = {
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

  it('evaluateMediaSeo flags missing alt on images', () => {
    expect(evaluateMediaSeo(baseMedia)).toBe('critical');
    expect(evaluateMediaSeo({ ...baseMedia, altText: 'Hero' })).toBe('ok');
  });

  it('evaluateContentSeo flags published content without description', () => {
    expect(
      evaluateContentSeo({
        status: 'published',
        frontMatter: {},
      })
    ).toBe('critical');

    expect(
      evaluateContentSeo({
        status: 'published',
        frontMatter: { seoDescription: 'About us page', seoTitle: 'About us' },
        featuredImage: '/storage/og.png',
        tags: ['cms'],
      })
    ).toBe('ok');
  });
});
