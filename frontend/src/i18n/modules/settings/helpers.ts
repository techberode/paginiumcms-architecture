type TranslateFn = (key: string, params?: Record<string, string | number>) => string;

export function translateSettingGroup(
  t: TranslateFn,
  groupKey: string,
  fallback: string
): string {
  const key = `settings.groups.${groupKey}`;
  const value = t(key);
  return value !== key ? value : fallback;
}

export function translateSettingFieldLabel(
  t: TranslateFn,
  groupKey: string,
  fieldKey: string,
  fallback: string
): string {
  const key = `settings.fields.${groupKey}.${fieldKey}.label`;
  const value = t(key);
  return value !== key ? value : fallback;
}

export function translateSettingFieldHelp(
  t: TranslateFn,
  groupKey: string,
  fieldKey: string,
  fallback: string | undefined
): string | undefined {
  if (!fallback) {
    return undefined;
  }
  const key = `settings.fields.${groupKey}.${fieldKey}.help`;
  const value = t(key);
  return value !== key ? value : fallback;
}

export function translateSettingEnumOption(
  t: TranslateFn,
  fieldKey: string,
  option: string,
  fallback: string
): string {
  const key = `settings.enum.${fieldKey}.${option}`;
  const value = t(key);
  return value !== key ? value : fallback;
}
