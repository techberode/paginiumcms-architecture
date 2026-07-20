import { describe, expect, it } from 'vitest';
import type { Article } from '../api/types';
import {
  blogSortToApiSort,
  buildBlogListPath,
  getAdjacentArticles,
  parseBlogSort,
  resolveBlogItemsPerPage,
  sortPublishedArticles,
} from './blogArticles';

function article(slug: string, title: string, date: string): Article {
  return {
    id: slug,
    slug,
    title,
    status: 'published',
    content: 'body',
    html: '<p>body</p>',
    author: 'Redakcia',
    createdAt: date,
    updatedAt: date,
    frontMatter: { date },
    featuredImage: '',
    tags: [],
    excerpt: '',
    readingTime: 1,
  };
}

describe('blogArticles', () => {
  it('sorts newest first by default', () => {
    const sorted = sortPublishedArticles(
      [article('b', 'B', '2026-01-01'), article('a', 'A', '2026-06-01')],
      'newest'
    );
    expect(sorted.map((item) => item.slug)).toEqual(['a', 'b']);
  });

  it('sorts oldest and title modes', () => {
    const items = [article('b', 'Beta', '2026-06-01'), article('a', 'Alpha', '2026-01-01')];
    expect(sortPublishedArticles(items, 'oldest').map((item) => item.slug)).toEqual(['a', 'b']);
    expect(sortPublishedArticles(items, 'title').map((item) => item.slug)).toEqual(['a', 'b']);
  });

  it('returns adjacent articles in list order', () => {
    const items = sortPublishedArticles(
      [article('one', 'One', '2026-01-01'), article('two', 'Two', '2026-02-01'), article('three', 'Three', '2026-03-01')],
      'newest'
    );
    const adjacent = getAdjacentArticles(items, 'two');
    expect(adjacent.prev?.slug).toBe('three');
    expect(adjacent.next?.slug).toBe('one');
  });

  it('builds blog list path with page, tag, and sort', () => {
    expect(buildBlogListPath({ page: 2, tag: 'news', sort: 'oldest' })).toBe(
      '/blog?page=2&tag=news&sort=oldest'
    );
    expect(buildBlogListPath({})).toBe('/blog');
  });

  it('resolves blog page size from settings', () => {
    expect(resolveBlogItemsPerPage({ blogItemsPerPage: 6, itemsPerPage: 20 })).toBe(6);
    expect(resolveBlogItemsPerPage({ itemsPerPage: 12 })).toBe(12);
    expect(resolveBlogItemsPerPage(undefined)).toBe(6);
  });

  it('parses blog sort query values', () => {
    expect(parseBlogSort('oldest')).toBe('oldest');
    expect(parseBlogSort('invalid')).toBe('newest');
  });

  it('maps blog sort to API sort params', () => {
    expect(blogSortToApiSort('newest')).toBe('-createdAt');
    expect(blogSortToApiSort('oldest')).toBe('createdAt');
    expect(blogSortToApiSort('title')).toBe('title');
  });
});
