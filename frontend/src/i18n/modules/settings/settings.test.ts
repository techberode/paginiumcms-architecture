import { describe, expect, it, afterEach } from 'vitest';
import { settingsEn } from './en';
import { settingsSk } from './sk';
import {
  translateSettingFieldLabel,
  translateSettingGroup,
} from './helpers';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

describe('settings i18n module', () => {
  afterEach(() => {
    resetI18nModulesForTests();
  });

  it('registers settings groups and field labels', () => {
    registerModuleMessages('sk', 'settings', settingsSk);
    registerModuleMessages('en', 'settings', settingsEn);

    expect(translate('sk', 'settings.page.title')).toBe('Nastavenia');
    expect(translate('en', 'settings.page.title')).toBe('Settings');
    expect(translate('en', 'settings.groups.general')).toBe('General');
    expect(translate('en', 'settings.fields.general.language.label')).toBe('Admin language');
    expect(translate('en', 'settings.enum.language.sk')).toBe('Slovak');
    expect(translate('sk', 'settings.fields.login.backgroundPicker.pickFromMedia')).toBe(
      'Vybrať z médií'
    );
    expect(translate('sk', 'settings.fields.login.backgroundPicker.uploadLocal')).toBe(
      'Nahrať z disku'
    );
    expect(translate('en', 'settings.fields.workflows.registrationOtpEnabled.label')).toBe(
      'OTP on registration'
    );
    expect(translate('en', 'settings.fields.workflows.otpTtlMinutes.label')).toBe(
      'OTP code validity (min)'
    );
    expect(translate('en', 'settings.fields.login.backgroundPicker.pickFromMedia')).toBe(
      'Pick from media'
    );
  });

  it('falls back to schema labels when translation key is missing', () => {
    const t = (key: string) => key;
    expect(translateSettingGroup(t, 'custom', 'Custom group')).toBe('Custom group');
    expect(translateSettingFieldLabel(t, 'general', 'unknown', 'Fallback label')).toBe(
      'Fallback label'
    );
  });
});
