// frontend/src/i18n/registerModules.ts
// FE i18n module catalogs (It.18).
import { registerModuleMessages } from './index';
import { adminEn } from './modules/admin/en';
import { adminSk } from './modules/admin/sk';
import { contentEn } from './modules/content/en';
import { contentSk } from './modules/content/sk';
import { listEn } from './modules/list/en';
import { listSk } from './modules/list/sk';
import { settingsEn } from './modules/settings/en';
import { settingsSk } from './modules/settings/sk';
import { translationsEn } from './modules/translations/en';
import { translationsSk } from './modules/translations/sk';
import { usersEn } from './modules/users/en';
import { usersSk } from './modules/users/sk';
import { dashboardEn } from './modules/dashboard/en';
import { dashboardSk } from './modules/dashboard/sk';
import { navigationEn } from './modules/navigation/en';
import { navigationSk } from './modules/navigation/sk';
import { mediaEn } from './modules/media/en';
import { mediaSk } from './modules/media/sk';
import { analyticsEn } from './modules/analytics/en';
import { analyticsSk } from './modules/analytics/sk';

export function registerAllI18nModules(): void {
  registerModuleMessages('sk', 'admin', adminSk);
  registerModuleMessages('en', 'admin', adminEn);
  registerModuleMessages('sk', 'list', listSk);
  registerModuleMessages('en', 'list', listEn);
  registerModuleMessages('sk', 'content', contentSk);
  registerModuleMessages('en', 'content', contentEn);
  registerModuleMessages('sk', 'settings', settingsSk);
  registerModuleMessages('en', 'settings', settingsEn);
  registerModuleMessages('sk', 'translations', translationsSk);
  registerModuleMessages('en', 'translations', translationsEn);
  registerModuleMessages('sk', 'users', usersSk);
  registerModuleMessages('en', 'users', usersEn);
  registerModuleMessages('sk', 'dashboard', dashboardSk);
  registerModuleMessages('en', 'dashboard', dashboardEn);
  registerModuleMessages('sk', 'navigation', navigationSk);
  registerModuleMessages('en', 'navigation', navigationEn);
  registerModuleMessages('sk', 'media', mediaSk);
  registerModuleMessages('en', 'media', mediaEn);
  registerModuleMessages('sk', 'analytics', analyticsSk);
  registerModuleMessages('en', 'analytics', analyticsEn);
}

registerAllI18nModules();
