import { describe, expect, it } from 'vitest';
import {
  findNavigationMatches,
  resolvePreviewPath,
  resolvePublicPath,
  resolveStoragePath,
  slugifyTitle,
} from './contentEditorMeta';

describe('contentEditorMeta', () => {
  it('slugifies titles', () => {
    expect(slugifyTitle('PaginiumCMS | Budúcnosť')).toBe('paginiumcms-buducnost');
  });

  it('resolves public paths', () => {
    expect(resolvePublicPath('page', 'home')).toBe('/home');
    expect(resolvePublicPath('article', 'uvod')).toBe('/blog/uvod');
  });

  it('resolves storage paths', () => {
    expect(resolveStoragePath('page', 'home')).toBe('content/pages/home.md');
    expect(resolveStoragePath('page', 'home', 'pages/home.md')).toBe('content/pages/home.md');
  });

  it('resolves preview paths', () => {
    expect(resolvePreviewPath('page', 'home')).toBe('/preview/home');
    expect(resolvePreviewPath('article', 'uvod')).toBe('/blog/uvod');
  });

  it('finds navigation matches by public path', () => {
    const items = [
      { id: '1', label: 'Home', path: '/home', order: 0 },
      { id: '2', label: 'Blog', path: '/blog', order: 1 },
    ];

    expect(findNavigationMatches(items, 'page', 'home')).toHaveLength(1);
    expect(findNavigationMatches(items, 'page', 'home')[0]?.label).toBe('Home');
    expect(findNavigationMatches(items, 'page', 'about')).toHaveLength(0);
  });
});
