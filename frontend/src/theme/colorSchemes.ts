export const COLOR_SCHEME_IDS = [
  'indigo-classic',
  'ocean-slate',
  'forest-sage',
  'sunset-rose',
  'mono-zinc',
] as const;

export type ColorSchemeId = (typeof COLOR_SCHEME_IDS)[number];

export type AppearanceMode = 'light' | 'dark' | 'system';

export type ResolvedTheme = 'light' | 'dark';

export interface ColorTokens {
  primary: string;
  primaryForeground: string;
  secondary: string;
  surface: string;
  surfaceElevated: string;
  text: string;
  textMuted: string;
  accent: string;
  border: string;
}

export interface ColorSchemeDefinition {
  id: ColorSchemeId;
  nameKey: string;
  descriptionKey: string;
  light: ColorTokens;
  dark: ColorTokens;
}

export const COLOR_SCHEMES: ColorSchemeDefinition[] = [
  {
    id: 'indigo-classic',
    nameKey: 'settings.appearance.schemes.indigoClassic.name',
    descriptionKey: 'settings.appearance.schemes.indigoClassic.description',
    light: {
      primary: '#4f46e5',
      primaryForeground: '#ffffff',
      secondary: '#64748b',
      surface: '#f8fafc',
      surfaceElevated: '#ffffff',
      text: '#0f172a',
      textMuted: '#64748b',
      accent: '#7c3aed',
      border: '#e2e8f0',
    },
    dark: {
      primary: '#818cf8',
      primaryForeground: '#0f172a',
      secondary: '#94a3b8',
      surface: '#0f172a',
      surfaceElevated: '#1e293b',
      text: '#f1f5f9',
      textMuted: '#94a3b8',
      accent: '#a78bfa',
      border: '#334155',
    },
  },
  {
    id: 'ocean-slate',
    nameKey: 'settings.appearance.schemes.oceanSlate.name',
    descriptionKey: 'settings.appearance.schemes.oceanSlate.description',
    light: {
      primary: '#0d9488',
      primaryForeground: '#ffffff',
      secondary: '#64748b',
      surface: '#f8fafc',
      surfaceElevated: '#ffffff',
      text: '#0f172a',
      textMuted: '#64748b',
      accent: '#14b8a6',
      border: '#e2e8f0',
    },
    dark: {
      primary: '#2dd4bf',
      primaryForeground: '#042f2e',
      secondary: '#94a3b8',
      surface: '#0f172a',
      surfaceElevated: '#1e293b',
      text: '#ecfeff',
      textMuted: '#94a3b8',
      accent: '#5eead4',
      border: '#334155',
    },
  },
  {
    id: 'forest-sage',
    nameKey: 'settings.appearance.schemes.forestSage.name',
    descriptionKey: 'settings.appearance.schemes.forestSage.description',
    light: {
      primary: '#059669',
      primaryForeground: '#ffffff',
      secondary: '#78716c',
      surface: '#fafaf9',
      surfaceElevated: '#ffffff',
      text: '#1c1917',
      textMuted: '#78716c',
      accent: '#84cc16',
      border: '#e7e5e4',
    },
    dark: {
      primary: '#34d399',
      primaryForeground: '#052e16',
      secondary: '#a8a29e',
      surface: '#1c1917',
      surfaceElevated: '#292524',
      text: '#fafaf9',
      textMuted: '#a8a29e',
      accent: '#a3e635',
      border: '#44403c',
    },
  },
  {
    id: 'sunset-rose',
    nameKey: 'settings.appearance.schemes.sunsetRose.name',
    descriptionKey: 'settings.appearance.schemes.sunsetRose.description',
    light: {
      primary: '#e11d48',
      primaryForeground: '#ffffff',
      secondary: '#a8a29e',
      surface: '#fffbeb',
      surfaceElevated: '#ffffff',
      text: '#292524',
      textMuted: '#78716c',
      accent: '#fb7185',
      border: '#fde68a',
    },
    dark: {
      primary: '#fb7185',
      primaryForeground: '#4c0519',
      secondary: '#a8a29e',
      surface: '#1c1917',
      surfaceElevated: '#292524',
      text: '#fff1f2',
      textMuted: '#a8a29e',
      accent: '#fda4af',
      border: '#44403c',
    },
  },
  {
    id: 'mono-zinc',
    nameKey: 'settings.appearance.schemes.monoZinc.name',
    descriptionKey: 'settings.appearance.schemes.monoZinc.description',
    light: {
      primary: '#18181b',
      primaryForeground: '#ffffff',
      secondary: '#71717a',
      surface: '#fafafa',
      surfaceElevated: '#ffffff',
      text: '#18181b',
      textMuted: '#71717a',
      accent: '#52525b',
      border: '#e4e4e7',
    },
    dark: {
      primary: '#fafafa',
      primaryForeground: '#18181b',
      secondary: '#a1a1aa',
      surface: '#09090b',
      surfaceElevated: '#18181b',
      text: '#fafafa',
      textMuted: '#a1a1aa',
      accent: '#d4d4d8',
      border: '#3f3f46',
    },
  },
];

export const DEFAULT_COLOR_SCHEME_ID: ColorSchemeId = 'indigo-classic';

export function isColorSchemeId(value: string): value is ColorSchemeId {
  return (COLOR_SCHEME_IDS as readonly string[]).includes(value);
}

export function getColorScheme(id: string): ColorSchemeDefinition {
  return COLOR_SCHEMES.find((scheme) => scheme.id === id) ?? COLOR_SCHEMES[0];
}

export function resolveThemeMode(mode: AppearanceMode): ResolvedTheme {
  if (mode === 'light' || mode === 'dark') {
    return mode;
  }
  if (typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
    return 'dark';
  }
  return 'light';
}

export function swatchColors(scheme: ColorSchemeDefinition, theme: ResolvedTheme): string[] {
  const tokens = theme === 'dark' ? scheme.dark : scheme.light;
  return [tokens.primary, tokens.secondary, tokens.surface, tokens.text, tokens.accent];
}
