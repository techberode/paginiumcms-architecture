import { describe, expect, it, afterEach } from 'vitest';
import { dashboardEn } from './en';
import { dashboardSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('dashboard i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers dashboard catalogs', () => {
    registerModuleMessages('sk', 'dashboard', dashboardSk);
    registerModuleMessages('en', 'dashboard', dashboardEn);

    expect(translate('sk', 'dashboard.hero.title')).toBe('Vitajte v Riadiacom Centre');
    expect(translate('en', 'dashboard.kpi.pages')).toBe('Pages');
  });
});
