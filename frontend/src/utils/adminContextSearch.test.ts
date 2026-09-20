import { afterEach, describe, expect, it } from 'vitest';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../i18n';
import { adminEn } from '../i18n/modules/admin/en';
import { originEn } from '../i18n/modules/origin/en';
import { platformEn } from '../i18n/modules/platform/en';
import { projectPlannerEn } from '../i18n/modules/projectPlanner/en';
import { settingsEn } from '../i18n/modules/settings/en';
import {
  buildAdminContextSearchItems,
  foldAdminSearchText,
  mergeAdminSearchResults,
} from './adminContextSearch';

function t(key: string, params?: Record<string, string | number>): string {
  return translate('en', key, params);
}

describe('adminContextSearch', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  const register = () => {
    registerModuleMessages('en', 'settings', settingsEn);
    registerModuleMessages('en', 'platform', platformEn);
    registerModuleMessages('en', 'admin', adminEn);
    registerModuleMessages('en', 'origin', originEn);
    registerModuleMessages('en', 'projectPlanner', projectPlannerEn);
  };

  it('folds Slovak diacritics so nastavenia matches nastavenia', () => {
    expect(foldAdminSearchText('Nastavenia')).toContain('nastavenia');
    expect(foldAdminSearchText('časové')).toBe('casove');
  });

  it('finds a setting by helper text', () => {
    register();
    const items = buildAdminContextSearchItems(t, 'DST', ['ADMIN']);
    expect(items.some((item) => item.adminPath.includes('timezoneDst'))).toBe(true);
    expect(items.some((item) => item.type === 'help' || item.type === 'setting')).toBe(true);
  });

  it('finds SMTP settings from an explanation', () => {
    register();
    const items = buildAdminContextSearchItems(t, 'SMTP', ['ADMIN']);
    expect(items.some((item) => item.adminPath.includes('group=smtp') || item.title.toLowerCase().includes('smtp'))).toBe(
      true
    );
  });

  it('finds the AI assistant group', () => {
    register();
    const items = buildAdminContextSearchItems(t, 'assistant', ['ADMIN']);
    expect(items.some((item) => item.adminPath.includes('group=agent'))).toBe(true);
  });

  it('merges context hits with remote content instead of replacing them', () => {
    const merged = mergeAdminSearchResults(
      [{ type: 'setting', title: 'Timezone', path: '/settings?group=general&field=timezone', adminPath: '/settings?group=general&field=timezone' }],
      [{ type: 'page', title: 'About', path: '/about', adminPath: '/pages/about' }]
    );
    expect(merged).toHaveLength(2);
    expect(merged.map((item) => item.type)).toEqual(['setting', 'page']);
  });
});
