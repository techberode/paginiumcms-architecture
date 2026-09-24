export type TextScaleScope = 'public' | 'admin';

export const TEXT_SCALE_MIN = 100;
export const TEXT_SCALE_MAX = 200;
export const TEXT_SCALE_STEP = 10;

const STORAGE_KEYS: Record<TextScaleScope, string> = {
  public: 'paginium.textScale.public',
  admin: 'paginium.textScale.admin',
};

const ACTIVE_KEYS: Record<TextScaleScope, string> = {
  public: 'paginium.textScale.public.active',
  admin: 'paginium.textScale.admin.active',
};

export function clampTextScalePercent(value: number): number {
  const stepped = Math.round(value / TEXT_SCALE_STEP) * TEXT_SCALE_STEP;
  return Math.min(TEXT_SCALE_MAX, Math.max(TEXT_SCALE_MIN, stepped));
}

export function readStoredTextScale(scope: TextScaleScope): { active: boolean; percent: number } {
  if (typeof window === 'undefined') {
    return { active: false, percent: 100 };
  }

  const active = window.localStorage.getItem(ACTIVE_KEYS[scope]) === '1';
  const raw = Number.parseInt(window.localStorage.getItem(STORAGE_KEYS[scope]) ?? '100', 10);
  const percent = clampTextScalePercent(Number.isFinite(raw) ? raw : 100);

  return { active, percent: active ? percent : 100 };
}

export function writeStoredTextScale(scope: TextScaleScope, active: boolean, percent: number): void {
  if (typeof window === 'undefined') {
    return;
  }

  const clamped = clampTextScalePercent(percent);
  window.localStorage.setItem(ACTIVE_KEYS[scope], active ? '1' : '0');
  window.localStorage.setItem(STORAGE_KEYS[scope], String(clamped));
}

export function applyDocumentTextScale(scope: TextScaleScope, active: boolean, percent: number): void {
  if (typeof document === 'undefined') {
    return;
  }

  const html = document.documentElement;
  const attr = scope === 'public' ? 'data-public-text-scale' : 'data-admin-text-scale';
  const effective = active ? clampTextScalePercent(percent) : 100;

  if (effective === 100) {
    html.removeAttribute(attr);
    html.style.removeProperty('font-size');
    return;
  }

  html.setAttribute(attr, String(effective));
  html.style.fontSize = `${effective}%`;
}
