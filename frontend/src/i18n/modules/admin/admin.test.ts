import { describe, expect, it, afterEach } from 'vitest';
import { adminEn } from './en';
import { adminSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

const NAV_KEYS = [
  'dashboard',
  'pages',
  'articles',
  'media',
  'navigation',
  'comments',
  'messages',
  'github',
  'codeEditor',
  'backups',
  'trash',
  'firewall',
  'logs',
  'audit',
  'securityAudit',
  'securityAcl',
  'blueprints',
  'extensions',
  'demo',
  'notifications',
  'scheduler',
  'users',
  'accountSecurity',
  'settings',
] as const;

function navTree(locale: typeof adminSk): Record<string, string> {
  return locale.nav as Record<string, string>;
}

describe('admin i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('defines matching nav keys in sk and en catalogs', () => {
    const skNav = navTree(adminSk);
    const enNav = navTree(adminEn);
    for (const key of NAV_KEYS) {
      expect(skNav[key], `sk nav.${key}`).toBeTruthy();
      expect(enNav[key], `en nav.${key}`).toBeTruthy();
    }
  });

  it('registers admin namespace for translate()', () => {
    registerModuleMessages('sk', 'admin', adminSk);
    registerModuleMessages('en', 'admin', adminEn);

    expect(translate('sk', 'admin.nav.dashboard')).toBe('Prehľad');
    expect(translate('en', 'admin.nav.dashboard')).toBe('Dashboard');
    expect(translate('sk', 'admin.header.viewWebsite')).toBe('Zobraziť web');
    expect(translate('en', 'admin.header.viewWebsite')).toBe('View website');
    expect(translate('sk', 'admin.header.purgeCache')).toBe('Vymazať cache');
    expect(translate('en', 'admin.header.purgeCache')).toBe('Clear cache');
  });
});
