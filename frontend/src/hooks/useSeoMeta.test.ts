// frontend/src/hooks/useSeoMeta.test.ts
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { applySeoMeta, type SeoMeta } from './useSeoMeta';

const sampleMeta: SeoMeta = {
  title: 'Test Page | Paginium',
  description: 'Test description',
  canonical: 'https://example.com/test',
  robots: 'index,follow',
  openGraph: {
    title: 'Test Page | Paginium',
    description: 'Test description',
    url: 'https://example.com/test',
    type: 'website',
    image: 'https://example.com/og.png',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Test Page | Paginium',
    description: 'Test description',
    image: 'https://example.com/og.png',
  },
  jsonLd: {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    name: 'Test Page | Paginium',
  },
};

describe('applySeoMeta', () => {
  beforeEach(() => {
    document.head.innerHTML = '';
  });

  afterEach(() => {
    document.head.innerHTML = '';
  });

  it('sets document title and meta tags', () => {
    applySeoMeta(sampleMeta);

    expect(document.title).toBe('Test Page | Paginium');
    expect(document.querySelector('meta[name="description"]')?.getAttribute('content')).toBe(
      'Test description'
    );
    expect(document.querySelector('meta[property="og:title"]')?.getAttribute('content')).toBe(
      'Test Page | Paginium'
    );
    expect(document.querySelector('link[rel="canonical"]')?.getAttribute('href')).toBe(
      'https://example.com/test'
    );
    expect(document.getElementById('paginium-json-ld')?.textContent).toContain('WebPage');
  });
});
