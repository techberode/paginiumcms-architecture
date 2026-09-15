import type { PublicSettings } from '../api/settings';

const PUBLIC_SETTINGS_GROUPS = [
  'general',
  'branding',
  'maintenance',
  'workflows',
  'ui',
  'navigationUi',
  'navigation',
  'content',
  'editor',
  'notifications',
  'feeds',
  'comments',
  'contact',
  'newsletter',
  'privacy',
  'appearance',
  'layout',
  'company',
  'demo',
  'origin',
  'projectPlanner',
  'social',
  'gallery',
  'login',
  'security',
  'cmsInfo',
] as const satisfies ReadonlyArray<keyof PublicSettings>;

function isPlainObject(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

export function isPublicSettingsGroup(group: string): group is keyof PublicSettings {
  return (PUBLIC_SETTINGS_GROUPS as readonly string[]).includes(group);
}

export function mergeSettingsPatch(
  base: Record<string, unknown>,
  patch: Record<string, unknown>
): Record<string, unknown> {
  const next = { ...base };

  for (const key of Object.keys(patch)) {
    const incoming = patch[key];
    if (incoming === undefined) {
      continue;
    }

    const current = next[key];
    if (isPlainObject(incoming) && isPlainObject(current)) {
      next[key] = { ...current, ...incoming };
      continue;
    }

    next[key] = incoming;
  }

  return next;
}

export function mergePublicSettings(
  base: PublicSettings,
  patch: Partial<PublicSettings>
): PublicSettings {
  return mergeSettingsPatch(
    base as unknown as Record<string, unknown>,
    patch as Record<string, unknown>
  ) as unknown as PublicSettings;
}

export function omitPreviewGroup(
  patch: Partial<PublicSettings>,
  group: string
): Partial<PublicSettings> | null {
  if (!(group in patch)) {
    return Object.keys(patch).length ? patch : null;
  }

  const next = { ...patch };
  delete next[group as keyof PublicSettings];
  return Object.keys(next).length ? next : null;
}

export function previewPatchFromGroup(
  group: string,
  values: Record<string, unknown>
): Partial<PublicSettings> | null {
  if (!isPublicSettingsGroup(group)) {
    return null;
  }

  return { [group]: values } as Partial<PublicSettings>;
}
