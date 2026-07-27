export type SettingsCategoryId = 'system' | 'site' | 'media' | 'security';

export interface SettingsCategoryDef {
  id: SettingsCategoryId;
  labelKey: string;
  descriptionKey: string;
  groups: string[];
}

/** Nastavenia zoskupené podľa dôležitosti (It.19). */
export const SETTINGS_CATEGORIES: SettingsCategoryDef[] = [
  {
    id: 'system',
    labelKey: 'settings.categories.system.label',
    descriptionKey: 'settings.categories.system.description',
    groups: [
      'general',
      'cmsInfo',
      'maintenance',
      'logging',
      'adminUi',
      'monitoring',
      'notifications',
      'connectors',
      'smtp',
      'scheduler',
      'workflows',
      'codePolicy',
    ],
  },
  {
    id: 'site',
    labelKey: 'settings.categories.site.label',
    descriptionKey: 'settings.categories.site.description',
    groups: ['branding', 'appearance', 'content', 'editor', 'seo', 'feeds', 'comments', 'contact', 'newsletter', 'company', 'login'],
  },
  {
    id: 'media',
    labelKey: 'settings.categories.media.label',
    descriptionKey: 'settings.categories.media.description',
    groups: ['media'],
  },
  {
    id: 'security',
    labelKey: 'settings.categories.security.label',
    descriptionKey: 'settings.categories.security.description',
    groups: ['security', 'accessControl', 'firewall', 'sso', 'contentSecurity', 'uploadSecurity'],
  },
];

export function resolveSettingsCategory(groupKey: string): SettingsCategoryId {
  for (const category of SETTINGS_CATEGORIES) {
    if (category.groups.includes(groupKey)) {
      return category.id;
    }
  }
  return 'system';
}

export function groupsForCategory(categoryId: SettingsCategoryId, available: string[]): string[] {
  const category = SETTINGS_CATEGORIES.find((entry) => entry.id === categoryId);
  if (!category) {
    return available;
  }
  return category.groups.filter((group) => available.includes(group));
}
