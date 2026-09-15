const NAV_SECTIONS_KEY = 'paginium.admin.navSections';

export function loadOpenNavSections(sectionIds: string[]): Record<string, boolean> {
  const defaults = Object.fromEntries(sectionIds.map((id) => [id, true]));
  if (typeof window === 'undefined') {
    return defaults;
  }

  try {
    const raw = window.localStorage.getItem(NAV_SECTIONS_KEY);
    if (!raw) {
      return defaults;
    }
    const parsed: unknown = JSON.parse(raw);
    if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
      return defaults;
    }
    const overlay: Record<string, boolean> = {};
    for (const [key, value] of Object.entries(parsed as Record<string, unknown>)) {
      if (typeof value === 'boolean') {
        overlay[key] = value;
      }
    }
    return { ...defaults, ...overlay };
  } catch {
    return defaults;
  }
}

export function saveOpenNavSections(state: Record<string, boolean>): void {
  if (typeof window === 'undefined') {
    return;
  }
  window.localStorage.setItem(NAV_SECTIONS_KEY, JSON.stringify(state));
}

export const ADMIN_NAV_SECTIONS_STORAGE_KEY = NAV_SECTIONS_KEY;
