import type { Article } from '../api/types';
import { contentDateToTimestamp, resolveContentDate } from './contentDates';

export type BlogSort = 'newest' | 'oldest' | 'title';

export function blogSortToApiSort(sort: BlogSort): string {
  switch (sort) {
    case 'oldest':
      return 'createdAt';
    case 'title':
      return 'title';
    case 'newest':
    default:
      return '-createdAt';
  }
}

export function parseBlogSort(value: string | null): BlogSort {
  if (value === 'oldest' || value === 'title') {
    return value;
  }
  return 'newest';
}

export function getArticleDate(article: Article): number {
  return contentDateToTimestamp(resolveContentDate(article.frontMatter?.date, article.createdAt));
}

export function sortPublishedArticles(articles: Article[], sort: BlogSort): Article[] {
  const copy = [...articles];
  switch (sort) {
    case 'oldest':
      return copy.sort((a, b) => getArticleDate(a) - getArticleDate(b));
    case 'title':
      return copy.sort((a, b) => a.title.localeCompare(b.title, 'sk'));
    case 'newest':
    default:
      return copy.sort((a, b) => getArticleDate(b) - getArticleDate(a));
  }
}

export function getAdjacentArticles(
  articles: Article[],
  slug: string
): { prev: Article | null; next: Article | null; index: number } {
  const index = articles.findIndex((article) => article.slug === slug);
  if (index === -1) {
    return { prev: null, next: null, index: -1 };
  }

  return {
    prev: index > 0 ? articles[index - 1] : null,
    next: index < articles.length - 1 ? articles[index + 1] : null,
    index,
  };
}

export function buildBlogListPath(options: {
  page?: number;
  tag?: string | null;
  sort?: BlogSort;
}): string {
  const params = new URLSearchParams();
  if (options.page && options.page > 1) {
    params.set('page', String(options.page));
  }
  if (options.tag) {
    params.set('tag', options.tag);
  }
  if (options.sort && options.sort !== 'newest') {
    params.set('sort', options.sort);
  }
  const query = params.toString();
  return query ? `/blog?${query}` : '/blog';
}

export function resolveBlogItemsPerPage(contentSettings: Record<string, unknown> | undefined): number {
  const blogSize = Number(contentSettings?.blogItemsPerPage);
  if (Number.isFinite(blogSize) && blogSize >= 1) {
    return Math.min(100, Math.floor(blogSize));
  }
  const fallback = Number(contentSettings?.itemsPerPage ?? 6);
  return Number.isFinite(fallback) && fallback >= 1 ? Math.min(100, Math.floor(fallback)) : 6;
}
