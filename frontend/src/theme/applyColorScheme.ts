import {
  type AppearanceMode,
  type ColorSchemeId,
  DEFAULT_COLOR_SCHEME_ID,
  getColorScheme,
  isColorSchemeId,
  resolveThemeMode,
  type ResolvedTheme,
} from './colorSchemes';

const TOKEN_KEYS = [
  'primary',
  'primaryForeground',
  'secondary',
  'surface',
  'surfaceElevated',
  'text',
  'textMuted',
  'accent',
  'border',
] as const;

function toCssVarName(key: (typeof TOKEN_KEYS)[number]): string {
  return `--color-${key.replace(/([A-Z])/g, '-$1').toLowerCase()}`;
}

export function applyColorSchemeTokens(
  root: HTMLElement,
  schemeId: ColorSchemeId,
  resolvedTheme: ResolvedTheme
): void {
  const scheme = getColorScheme(schemeId);
  const tokens = resolvedTheme === 'dark' ? scheme.dark : scheme.light;

  root.dataset.scheme = schemeId;
  root.dataset.theme = resolvedTheme;

  for (const key of TOKEN_KEYS) {
    root.style.setProperty(toCssVarName(key), tokens[key]);
  }

  root.classList.toggle('dark', resolvedTheme === 'dark');
}

export function applyColorScheme(
  schemeId: string,
  mode: AppearanceMode,
  root: HTMLElement = document.documentElement
): ResolvedTheme {
  const safeSchemeId = isColorSchemeId(schemeId) ? schemeId : DEFAULT_COLOR_SCHEME_ID;
  const resolvedTheme = resolveThemeMode(mode);
  applyColorSchemeTokens(root, safeSchemeId, resolvedTheme);
  return resolvedTheme;
}

export function clearColorScheme(root: HTMLElement = document.documentElement): void {
  delete root.dataset.scheme;
  delete root.dataset.theme;

  for (const key of TOKEN_KEYS) {
    root.style.removeProperty(toCssVarName(key));
  }
}
