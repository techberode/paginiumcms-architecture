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
}

registerAllI18nModules();
