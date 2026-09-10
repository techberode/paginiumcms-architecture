import { THEME_STUDIO_DRAFT_ID } from './themeStudioDraft';

const PERSIST_ID = /^[a-z][a-z0-9-]{0,63}$/;

export function isPersistableThemeId(id: string): boolean {
  return PERSIST_ID.test(id) && id !== 'new' && id !== 'paginium-core';
}

export function themeIdFromManifestJson(json: string): string | null {
  try {
    const parsed = JSON.parse(json) as { id?: unknown };
    if (typeof parsed.id !== 'string') {
      return null;
    }
    const id = parsed.id.trim();
    return isPersistableThemeId(id) ? id : null;
  } catch {
    return null;
  }
}

export function persistThemeId(routeId: string, files: Record<string, string>): string | null {
  const fromManifest = typeof files['theme.json'] === 'string'
    ? themeIdFromManifestJson(files['theme.json'])
    : null;

  if (routeId === THEME_STUDIO_DRAFT_ID || routeId === '') {
    return fromManifest;
  }

  if (!isPersistableThemeId(routeId)) {
    return null;
  }

  if (fromManifest !== null && fromManifest !== routeId) {
    return null;
  }

  return routeId;
}
