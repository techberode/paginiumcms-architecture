import { describe, expect, it, afterEach } from 'vitest';
import { contentEn } from './en';
import { contentSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('content i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers pages and articles catalogs', () => {
    registerModuleMessages('sk', 'content', contentSk);
    registerModuleMessages('en', 'content', contentEn);

    expect(translate('sk', 'content.pages.title')).toBe('Podstránky');
    expect(translate('en', 'content.articles.empty')).toBe('No articles found');
    expect(translate('sk', 'content.confirm.bulkDelete', { selected: '3', total: '12', count: '3' })).toContain('3 z 12');
  });
});
