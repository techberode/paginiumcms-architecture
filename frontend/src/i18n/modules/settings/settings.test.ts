import { describe, expect, it, afterEach } from 'vitest';
import { settingsEn } from './en';
import { settingsSk } from './sk';
import {
  translateSettingFieldLabel,
  translateSettingGroup,
} from './helpers';
import { registerModuleMessages, resetI18nModulesForTests, translate } from '../../index';

/** Mirror of backend PermissionCatalog::ALL — keep in sync when RBAC grows. */
const RBAC_PERMISSIONS = [
  'user:manage',
  'content:manage',
  'content:create',
  'content:edit',
  'content:delete',
  'content:view',
  'media:manage',
  'media:upload',
  'media:delete',
  'settings:manage',
  'git:publish',
  'gallery:manage',
  'logs:view',
  'metrics:read',
  'api-keys:manage',
  'redirects:manage',
  'webhooks:manage',
  'profile:edit',
] as const;

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
    expect(translate('sk', 'settings.privacy.panel.blocksTitle')).toBe('Vlastné GDPR bloky');
    expect(translate('en', 'settings.privacy.panel.addBlock')).toBe('Add block');
    expect(translate('sk', 'settings.fields.privacy.privacyContactName.label')).toBe(
      'Meno alebo prevádzkovateľ'
    );
  });

  it('covers all RBAC permission labels (sk + en)', () => {
    registerModuleMessages('sk', 'settings', settingsSk);
    registerModuleMessages('en', 'settings', settingsEn);

    for (const permission of RBAC_PERMISSIONS) {
      for (const locale of ['sk', 'en'] as const) {
        const key = `settings.accessControl.permissions.${permission}`;
        const label = translate(locale, key);
        expect(label).not.toBe(key);
        expect(label.length).toBeGreaterThan(0);
      }
    }
  });

  it('falls back to schema labels when translation key is missing', () => {
    const t = (key: string) => key;
    expect(translateSettingGroup(t, 'custom', 'Custom group')).toBe('Custom group');
    expect(translateSettingFieldLabel(t, 'general', 'unknown', 'Fallback label')).toBe(
      'Fallback label'
    );
  });
});
