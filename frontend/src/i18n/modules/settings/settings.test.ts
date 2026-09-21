import { describe, expect, it, afterEach } from 'vitest';
import { settingsEn } from './en';
import { settingsSk } from './sk';
import { SETTINGS_CATEGORIES } from './categories';
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
  'project-plan:read',
  'project-plan:manage',
  'time-entry:manage',
  'support-ticket:manage',
  'mail:read-own',
  'mail:read-all',
  'themes:read',
  'themes:edit',
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
    expect(translate('sk', 'settings.page.apply')).toBe('Použiť');
    expect(translate('en', 'settings.page.apply')).toBe('Apply');
    expect(translate('en', 'settings.groups.general')).toBe('General');
    expect(translate('en', 'settings.groups.imap')).toBe('Email / IMAP');
    expect(translate('sk', 'settings.groups.imap')).toBe('Email / IMAP');
    expect(SETTINGS_CATEGORIES.find((category) => category.id === 'system')?.groups).toContain(
      'imap'
    );
    expect(SETTINGS_CATEGORIES.find((category) => category.id === 'site')?.groups).toContain(
      'translation'
    );
    expect(SETTINGS_CATEGORIES.find((category) => category.id === 'site')?.groups).toContain(
      'agent'
    );
    expect(translate('en', 'settings.groups.agent')).toBe('CMS AI assistant');
    expect(translate('sk', 'settings.fields.agent.enabled.label')).toContain('asistent');
    expect(translate('en', 'settings.enum.provider.ollama')).toContain('Ollama');
    expect(translate('en', 'settings.agent.privacyWarning')).toContain('Apply');
    expect(translate('en', 'settings.groups.translation')).toBe('Assisted translation');
    expect(translate('sk', 'settings.fields.translation.enabled.label')).toBe(
      'Povoliť asistovaný preklad'
    );
    expect(translate('en', 'settings.translation.instanceRequired')).toContain('own');
    expect(translate('sk', 'settings.translation.instanceRequired')).toContain('vlastnú');
    expect(translate('en', 'settings.enum.provider.deepl')).toBe('DeepL');
    expect(translate('en', 'settings.enum.provider.google')).toContain('Google');
    expect(translate('sk', 'settings.fields.translation.deeplApiKey.label')).toBe('DeepL API kľúč');
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
    expect(translate('sk', 'settings.fields.engine.storageDriver.label')).toBe('Ovládač úložiska');
    expect(translate('sk', 'settings.fields.engine.schemaValidationEnabled.label')).toBe(
      'Validácia JSON Schema'
    );
    expect(translate('en', 'settings.fields.engine.storageDriver.label')).toBe('Storage driver');
    expect(translate('sk', 'settings.enum.cacheDriver.auto')).toBe('Automaticky');
    expect(translate('en', 'settings.enum.gitPublishStrategy.disabled')).toBe('Disabled');
    expect(translate('sk', 'settings.fields.content.shareEnabled.label')).toBe('Zdieľanie obsahu');
    expect(translate('en', 'settings.fields.content.shareX.label')).toBe('Share on X (Twitter)');
  });

  it('exposes extended tooltips for complex engine settings', () => {
    registerModuleMessages('en', 'settings', settingsEn);
    registerModuleMessages('sk', 'settings', settingsSk);

    expect(translate('en', 'settings.fields.engine.performanceGuardEnabled.tooltip')).toContain('Dashboard');
    expect(translate('sk', 'settings.fields.engine.performanceGuardEnabled.tooltip')).toContain('Dashboard');
    expect(translate('sk', 'settings.fields.engine.queryIndexDriver.label')).toBe(
      'Ovládač dopytového indexu katalógu'
    );
    expect(translate('en', 'settings.fields.engine.queryIndexRuntimeWatchEnabled.label')).toContain('Watch');
    expect(translate('sk', 'settings.enum.queryIndexDriver.json')).toContain('content.json');
    expect(translate('en', 'settings.enum.queryIndexDriver.sqlite')).toContain('derived');
    expect(translate('sk', 'settings.fields.ui.sidebarColor.label')).toBe('Farba bočného menu');
    expect(translate('en', 'settings.enum.navPlacement.top')).toBe('Top dropdown menu');
    expect(translate('sk', 'settings.enum.navPlacement.active')).toBe('Aktívne');
    expect(translate('en', 'settings.enum.chromeGradientDirection.to-right')).toBe('Right');
    expect(translate('en', 'settings.helpTooltip.toggle')).toBe('Show detailed help');
    expect(translate('sk', 'settings.helpTooltip.toggle')).toBe('Zobraziť podrobnú nápovedu');
    expect(translate('en', 'settings.layout.builders.outline.help')).toContain('visual canvas');
    expect(translate('sk', 'settings.layout.pagesOnlyHint')).toContain('Články');
    expect(translate('en', 'settings.fields.layout.builderMode.label')).toBe('Default layout builder');
    expect(translate('sk', 'settings.enum.builderMode.outline')).toBe('Outline blokov');
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
