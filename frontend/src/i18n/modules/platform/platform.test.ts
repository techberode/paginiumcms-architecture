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
    expect(translate('en', 'platform.themes.studio.edit')).toBe('Edit');
    expect(translate('en', 'platform.themes.studio.policyOk')).toBe('Policy OK');
    expect(translate('sk', 'platform.themes.studio.policyOk')).toBe('Politika OK');
    expect(translate('en', 'platform.themes.studio.previewBlocked')).toBe(
      'Preview blocked — fix policy markers in Monaco first.',
    );
    expect(translate('en', 'platform.themes.studio.normalize')).toBe('Normalize');
    expect(translate('en', 'platform.themes.studio.saved')).toBe('Theme package saved');
    expect(translate('sk', 'platform.themes.studio.slots')).toBe('Sloty');
    expect(translate('sk', 'platform.widgets.title')).toBe('Widgety');
    expect(translate('en', 'platform.widgets.copy')).toBe('Copy markdown');
    expect(translate('sk', 'platform.widgets.custom.title')).toBe('Vlastné widgety');
    expect(translate('sk', 'platform.teams.title')).toBe('Tímy');
    expect(translate('en', 'platform.teams.types.support')).toBe('Support');
    expect(translate('sk', 'platform.teams.namePlaceholder')).toBe('napr. Marketing');
    expect(translate('sk', 'platform.events.title')).toBe('Udalosti');
    expect(translate('en', 'platform.events.status.published')).toBe('Published');
    expect(translate('sk', 'platform.timeTracker.title')).toBe('Časovač');
    expect(translate('en', 'platform.timeTracker.start')).toBe('Start');
    expect(translate('sk', 'platform.account.title')).toBe('Účet');
    expect(translate('en', 'platform.account.tabs.profile')).toBe('Profile');
    expect(translate('sk', 'platform.account.tabs.public')).toBe('Verejná karta');
    expect(translate('en', 'platform.account.sections.details')).toBe('Profile details');
  });
});
