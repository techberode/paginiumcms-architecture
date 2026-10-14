import { describe, expect, it } from 'vitest';
import type { Article } from '../api/types';
import { buildArticleTeaser, buildArticleTeaserCollapsed } from './articleTeaser';

function article(content: string, excerpt = ''): Article {
  return {
    id: 'a',
    slug: 'a',
    title: 'Title',
    status: 'published',
    content,
    html: '',
    author: 'Author',
    createdAt: '2026-01-01',
    updatedAt: '2026-01-01',
    frontMatter: {},
    featuredImage: '',
    tags: [],
    excerpt,
    readingTime: 1,
  };
}

describe('articleTeaser', () => {
  it('prefers plain text from markdown body', () => {
    const teaser = buildArticleTeaser(
      article('# Úvod\n\nToto je **dlhší** úvodný odsek článku s viacerými vetami pre návštevníkov blogu.')
    );
    expect(teaser).toContain('Toto je');
    expect(teaser).not.toContain('**');
  });

  it('falls back to excerpt when body is too short', () => {
    const teaser = buildArticleTeaserCollapsed(article('Krátke.', 'Perex z metadát, ktorý slúži ako uputávka pre čitateľa na blogu.'));
    expect(teaser).toContain('Perex');
  });
});
