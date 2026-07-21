import { describe, expect, it, afterEach } from 'vitest';
import { listEn } from './en';
import { listSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('list i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers shared list strings', () => {
    registerModuleMessages('sk', 'list', listSk);
    registerModuleMessages('en', 'list', listEn);

    expect(translate('sk', 'list.status.published')).toBe('Publikované');
    expect(translate('en', 'list.toolbar.clearFilters')).toBe('Clear filters');
    expect(translate('sk', 'list.pagination.pageOf', { total: 10, page: 2, totalPages: 5 })).toContain(
      'strana 2 / 5'
    );
  });
});
