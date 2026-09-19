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
    expect(translate('sk', 'admin.header.twoFactorLink')).toBe('bezpečnosti účtu');
    expect(translate('en', 'admin.header.twoFactorLink')).toBe('account security');
    expect(translate('sk', 'admin.header.closeMenu')).toBe('Zavrieť menu');
    expect(translate('en', 'admin.topnav.label')).toBe('Administration menu');
    expect(translate('sk', 'admin.formActions.apply')).toBe('Použiť');
    expect(translate('en', 'admin.formActions.save')).toBe('Save');
    expect(translate('sk', 'admin.header.themeToDark')).toBe('Zapnúť tmavý režim');
    expect(translate('en', 'admin.header.themeToLight')).toBe('Switch to light mode');
    expect(translate('sk', 'admin.nav.widgets')).toBe('Widgety');
    expect(translate('en', 'admin.nav.widgets')).toBe('Widgets');
    expect(translate('sk', 'admin.nav.teams')).toBe('Tímy');
    expect(translate('en', 'admin.nav.teams')).toBe('Teams');
    expect(translate('sk', 'admin.nav.teamChat')).toBe('Tímový chat');
    expect(translate('en', 'admin.nav.teamChat')).toBe('Team chat');
    expect(translate('sk', 'admin.nav.events')).toBe('Udalosti');
    expect(translate('en', 'admin.nav.events')).toBe('Events');
    expect(translate('sk', 'admin.nav.timeTracker')).toBe('Časovač');
    expect(translate('en', 'admin.nav.timeTracker')).toBe('Time tracker');
    expect(translate('sk', 'admin.accountMenu.edit')).toBe('Upraviť účet');
    expect(translate('en', 'admin.accountMenu.public')).toBe('Public card');
  });
});
