import type { ContentType } from '../api/drafts';
import type { NavigationItem } from '../api/navigation';

export function slugifyTitle(title: string): string {
  return title
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

export function resolvePublicPath(type: ContentType, slug: string): string {
  const clean = slug.trim().replace(/^\/+/, '');
  if (!clean) {
    return type === 'article' ? '/blog' : '/';
  }

  return type === 'article' ? `/blog/${clean}` : `/${clean}`;
}

export function resolveStoragePath(
  type: ContentType,
  slug: string,
  pathFromApi?: string,
  storageFormat: 'md' | 'json' = 'md'
): string {
  if (pathFromApi?.trim()) {
    return pathFromApi.startsWith('content/') ? pathFromApi : `content/${pathFromApi}`;
  }

  const directory = type === 'article' ? 'blog' : 'pages';
  const ext = storageFormat === 'json' ? 'json' : 'md';
  const clean = slugifyTitle(slug) || 'new';

  return `content/${directory}/${clean}.${ext}`;
}

export function findNavigationMatches(
  items: NavigationItem[],
  type: ContentType,
  slug: string
): NavigationItem[] {
  const target = normalizeNavPath(resolvePublicPath(type, slug));
  if (!target) {
    return [];
  }

  return items.filter((item) => normalizeNavPath(item.path) === target);
}

export function normalizeNavPath(path: string): string {
  const trimmed = path.trim();
  if (!trimmed) {
    return '';
  }

  if (trimmed.startsWith('http://') || trimmed.startsWith('https://')) {
    return trimmed;
  }

  const withSlash = trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
  if (withSlash.length > 1 && withSlash.endsWith('/')) {
    return withSlash.slice(0, -1);
  }

  return withSlash;
}

export function resolvePreviewPath(type: ContentType, slug: string): string | undefined {
  const clean = slug.trim();
  if (!clean) {
    return undefined;
  }

  return type === 'article' ? `/blog/${clean}` : `/preview/${clean}`;
}

export function countContentStats(content: string): { characters: number; lines: number } {
  return {
    characters: content.length,
    lines: content === '' ? 0 : content.split('\n').length,
  };
}
