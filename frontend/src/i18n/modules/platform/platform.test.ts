import { describe, expect, it, afterEach } from 'vitest';
import { platformEn } from './en';
import { platformSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('platform i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers platform catalogs', () => {
    registerModuleMessages('sk', 'platform', platformSk);
    registerModuleMessages('en', 'platform', platformEn);

    expect(translate('sk', 'platform.firewall.title')).toBe('Firewall (WAF)');
    expect(translate('en', 'platform.scheduler.title')).toBe('Scheduler');
    expect(translate('sk', 'platform.commandPalette.types.article')).toBe('Článok');
  });
});
