import { describe, expect, it, afterEach } from 'vitest';
import { publicEn } from './en';
import { publicSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

function collectKeys(tree: Record<string, unknown>, prefix = ''): string[] {
  return Object.entries(tree).flatMap(([key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    if (typeof value === 'string') {
      return [path];
    }
    if (value && typeof value === 'object') {
      return collectKeys(value as Record<string, unknown>, path);
    }
    return [];
  });
}

describe('public i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers public catalogs', () => {
    registerModuleMessages('sk', 'public', publicSk);
    registerModuleMessages('en', 'public', publicEn);

    expect(translate('sk', 'public.nav.home')).toBe('Domov');
    expect(translate('en', 'public.nav.home')).toBe('Home');
    expect(translate('sk', 'public.blog.readingTime.one')).toBe('1 min čítania');
    expect(translate('en', 'public.auth.login.title')).toBe('Sign in');
  });

  it('keeps SK/EN key parity', () => {
    const skKeys = collectKeys(publicSk).sort();
    const enKeys = collectKeys(publicEn).sort();
    expect(skKeys).toEqual(enKeys);
  });
});
