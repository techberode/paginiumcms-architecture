/** Resolves tri-state bool settings from API (true/false/1/"true"/undefined). */
export function resolveBoolSetting(value: unknown, defaultValue = false): boolean {
  if (value === true || value === 1 || value === '1' || value === 'true') {
    return true;
  }
  if (value === false || value === 0 || value === '0' || value === 'false') {
    return false;
  }

  return defaultValue;
}

export function resolveArticlePrintEnabled(contentSettings: Record<string, unknown> | undefined): boolean {
  return resolveBoolSetting(contentSettings?.articlePrintEnabled, false);
}
