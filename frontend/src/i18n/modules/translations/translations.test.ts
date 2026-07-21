import { describe, expect, it, afterEach } from 'vitest';
import { translationsEn } from './en';
import { translationsSk } from './sk';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('translations i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers translation editor UI strings', () => {
    registerModuleMessages('sk', 'translations', translationsSk);
    registerModuleMessages('en', 'translations', translationsEn);

    expect(translate('sk', 'translations.page.title')).toBe('Preklady');
    expect(translate('en', 'translations.page.title')).toBe('Translations');
    expect(translate('en', 'translations.actions.save')).toBe('Save translations');
  });
});
