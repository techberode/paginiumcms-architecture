/** Named admin chrome colors (It.93). Independent of public appearance schemes. */

export const ADMIN_CHROME_COLOR_IDS = [
  'default',
  'navy',
  'slate',
  'indigo',
  'ocean',
  'forest',
  'wine',
  'charcoal',
] as const;

export type AdminChromeColorId = (typeof ADMIN_CHROME_COLOR_IDS)[number];
export type AdminNavPlacement = 'side' | 'top';
export type AdminGradientDirection =
  | 'to-bottom'
  | 'to-top'
  | 'to-right'
  | 'to-left'
  | 'to-bottom-right'
  | 'to-bottom-left';

export interface AdminChromePrefs {
  sidebarColor: AdminChromeColorId;
  topbarColor: AdminChromeColorId;
  gradient: boolean;
  gradientDirection: AdminGradientDirection;
  navPlacement: AdminNavPlacement;
}

export interface AdminChromeSwatch {
  id: AdminChromeColorId;
  swatch: string;
  gradientTo: string;
}

interface ChromeSurface {
  bg: string;
  gradientTo: string;
  hover: string;
  active: string;
  text: string;
  muted: string;
  activeText: string;
  border: string;
}

const COLORED: Record<Exclude<AdminChromeColorId, 'default'>, ChromeSurface> = {
  navy: {
    bg: '#0b1727',
    gradientTo: '#1e3a5f',
    hover: '#16324d',
    active: '#2563eb',
    text: '#ffffff',
    muted: '#e2e8f0',
    activeText: '#ffffff',
    border: '#243049',
  },
  slate: {
    bg: '#334155',
    gradientTo: '#475569',
    hover: '#3f4f64',
    active: '#0ea5e9',
    text: '#ffffff',
    muted: '#f1f5f9',
    activeText: '#ffffff',
    border: '#475569',
  },
  indigo: {
    bg: '#312e81',
    gradientTo: '#4338ca',
    hover: '#3730a3',
    active: '#6366f1',
    text: '#ffffff',
    muted: '#e0e7ff',
    activeText: '#ffffff',
    border: '#4338ca',
  },
  ocean: {
    bg: '#0e7490',
    gradientTo: '#155e75',
    hover: '#0f766e',
    active: '#22d3ee',
    text: '#ffffff',
    muted: '#ecfeff',
    activeText: '#083344',
    border: '#155e75',
  },
  forest: {
    bg: '#14532d',
    gradientTo: '#166534',
    hover: '#15803d',
    active: '#22c55e',
    text: '#ffffff',
    muted: '#dcfce7',
    activeText: '#052e16',
    border: '#166534',
  },
  wine: {
    bg: '#881337',
    gradientTo: '#9f1239',
    hover: '#be123c',
    active: '#fb7185',
    text: '#ffffff',
    muted: '#ffe4e6',
    activeText: '#4c0519',
    border: '#9f1239',
  },
  charcoal: {
    bg: '#18181b',
    gradientTo: '#27272a',
    hover: '#27272a',
    active: '#3b82f6',
    text: '#ffffff',
    muted: '#f4f4f5',
    activeText: '#ffffff',
    border: '#3f3f46',
  },
};

export const ADMIN_CHROME_SWATCHES: AdminChromeSwatch[] = [
  { id: 'default', swatch: '#edf2f9', gradientTo: '#ffffff' },
  { id: 'navy', swatch: COLORED.navy.bg, gradientTo: COLORED.navy.gradientTo },
  { id: 'slate', swatch: COLORED.slate.bg, gradientTo: COLORED.slate.gradientTo },
  { id: 'indigo', swatch: COLORED.indigo.bg, gradientTo: COLORED.indigo.gradientTo },
  { id: 'ocean', swatch: COLORED.ocean.bg, gradientTo: COLORED.ocean.gradientTo },
  { id: 'forest', swatch: COLORED.forest.bg, gradientTo: COLORED.forest.gradientTo },
  { id: 'wine', swatch: COLORED.wine.bg, gradientTo: COLORED.wine.gradientTo },
  { id: 'charcoal', swatch: COLORED.charcoal.bg, gradientTo: COLORED.charcoal.gradientTo },
];

export const ADMIN_GRADIENT_DIRECTIONS: Array<{ id: AdminGradientDirection; angle: string }> = [
  { id: 'to-bottom', angle: '180deg' },
  { id: 'to-top', angle: '0deg' },
  { id: 'to-right', angle: '90deg' },
  { id: 'to-left', angle: '270deg' },
  { id: 'to-bottom-right', angle: '135deg' },
  { id: 'to-bottom-left', angle: '225deg' },
];
export function isAdminChromeColorId(value: unknown): value is AdminChromeColorId {
  return typeof value === 'string' && (ADMIN_CHROME_COLOR_IDS as readonly string[]).includes(value);
}

export function isAdminNavPlacement(value: unknown): value is AdminNavPlacement {
  return value === 'side' || value === 'top';
}

export function isAdminGradientDirection(value: unknown): value is AdminGradientDirection {
  return ADMIN_GRADIENT_DIRECTIONS.some((entry) => entry.id === value);
}

export function resolveGradientAngle(direction: AdminGradientDirection): string {
  return ADMIN_GRADIENT_DIRECTIONS.find((entry) => entry.id === direction)?.angle ?? '180deg';
}

export function resolveAdminChrome(ui: Record<string, unknown> | undefined): AdminChromePrefs {
  const source = ui ?? {};
  return {
    sidebarColor: isAdminChromeColorId(source.sidebarColor) ? source.sidebarColor : 'default',
    topbarColor: isAdminChromeColorId(source.topbarColor) ? source.topbarColor : 'default',
    gradient: source.chromeGradient === true,
    gradientDirection: isAdminGradientDirection(source.chromeGradientDirection)
      ? source.chromeGradientDirection
      : 'to-bottom',
    navPlacement: isAdminNavPlacement(source.navPlacement) ? source.navPlacement : 'side',
  };
}

function applySurface(
  vars: Record<string, string>,
  prefix: 'sidebar' | 'topbar',
  color: AdminChromeColorId
): void {
  if (color === 'default') {
    return;
  }
  const surface = COLORED[color];
  if (prefix === 'sidebar') {
    vars['--admin-sidebar'] = surface.bg;
    vars['--admin-sidebar-hover'] = surface.hover;
    vars['--admin-sidebar-active'] = surface.active;
    vars['--admin-sidebar-text'] = surface.text;
    vars['--admin-sidebar-muted'] = surface.muted;
    vars['--admin-sidebar-active-text'] = surface.activeText;
  } else {
    vars['--admin-topbar'] = surface.bg;
    vars['--admin-topbar-text'] = surface.text;
    vars['--admin-topbar-muted'] = surface.muted;
    vars['--admin-topbar-accent'] = surface.text;
    vars['--admin-topbar-hover'] = surface.hover;
    vars['--admin-topbar-ink-shadow'] = '0 1px 2px rgba(0, 0, 0, 0.45)';
  }
}

function surfaceGradientEnd(color: AdminChromeColorId, fallback: string): string {
  return color === 'default' ? fallback : COLORED[color].gradientTo;
}

export function adminChromeCssVars(prefs: AdminChromePrefs): Record<string, string> {
  const vars: Record<string, string> = {
    '--admin-sidebar-gradient': 'none',
    '--admin-topbar-gradient': 'none',
    '--admin-topbar-ink-shadow': 'none',
  };

  applySurface(vars, 'sidebar', prefs.sidebarColor);
  applySurface(vars, 'topbar', prefs.topbarColor);

  if (prefs.gradient) {
    const angle = resolveGradientAngle(prefs.gradientDirection);
    const sideEnd = surfaceGradientEnd(prefs.sidebarColor, 'var(--admin-sidebar-hover)');
    const topEnd = surfaceGradientEnd(
      prefs.topbarColor,
      'color-mix(in srgb, var(--admin-topbar) 72%, var(--admin-topbar-text) 28%)'
    );
    vars['--admin-sidebar-gradient'] = `linear-gradient(${angle}, var(--admin-sidebar) 0%, ${sideEnd} 100%)`;
    vars['--admin-topbar-gradient'] = `linear-gradient(${angle}, var(--admin-topbar) 0%, ${topEnd} 100%)`;
    if (prefs.topbarColor !== 'default') {
      vars['--admin-topbar-ink-shadow'] = '0 1px 2px rgba(0, 0, 0, 0.5)';
    }
  }

  return vars;
}
