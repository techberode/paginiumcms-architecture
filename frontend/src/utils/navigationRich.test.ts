import { describe, expect, it } from 'vitest';
import {
  filterLucideIconCatalog,
  navigationItemHasVisual,
  normalizeLucideIconName,
  resolveNavigationIconComponent,
} from './navigationRich';

describe('navigationRich', () => {
  it('normalizes lucide icon names', () => {
    expect(normalizeLucideIconName('home')).toBe('Home');
    expect(normalizeLucideIconName('book-open')).toBe('BookOpen');
    expect(normalizeLucideIconName('Settings')).toBe('Settings');
  });

  it('resolves known lucide icons case-insensitively', () => {
    expect(resolveNavigationIconComponent('Newspaper')).not.toBeNull();
    expect(resolveNavigationIconComponent('newspaper')).not.toBeNull();
    expect(resolveNavigationIconComponent('book-open')).not.toBeNull();
  });

  it('returns null for unknown lucide icons', () => {
    expect(resolveNavigationIconComponent('NotARealLucideIconName123')).toBeNull();
  });

  it('lists default lucide icons and filters by name', () => {
    const defaults = filterLucideIconCatalog('');
    expect(defaults[0]).toBe('Home');
    expect(defaults).toContain('Mail');
    expect(filterLucideIconCatalog('mail')).toContain('Mail');
    expect(filterLucideIconCatalog('book open')).toContain('BookOpen');
  });

  it('navigationItemHasVisual requires resolvable icon', () => {
    expect(navigationItemHasVisual('lucide', 'Home')).toBe(true);
    expect(navigationItemHasVisual('lucide', 'NotARealLucideIconName123')).toBe(false);
    expect(navigationItemHasVisual('none', 'Home')).toBe(false);
  });
});
