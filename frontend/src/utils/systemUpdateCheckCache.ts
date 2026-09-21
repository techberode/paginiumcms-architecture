import type { SystemUpdateCheckResult, SystemUpdateStatus } from '../api/systemUpdate';

const STORAGE_KEY = 'paginium:system-update-check-cache:v1';
const SESSION_AUTO_KEY = 'paginium:system-update-session-auto:v1';

export interface SystemUpdateCheckCacheEntry {
  checkedAt: number;
  check: SystemUpdateCheckResult;
  status: SystemUpdateStatus | null;
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null;
}

export function readSystemUpdateCheckCache(): SystemUpdateCheckCacheEntry | null {
  if (typeof window === 'undefined') {
    return null;
  }
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) {
      return null;
    }
    const parsed: unknown = JSON.parse(raw);
    if (!isRecord(parsed) || typeof parsed.checkedAt !== 'number' || !isRecord(parsed.check)) {
      return null;
    }
    return {
      checkedAt: parsed.checkedAt,
      check: parsed.check as unknown as SystemUpdateCheckResult,
      status: isRecord(parsed.status) ? (parsed.status as unknown as SystemUpdateStatus) : null,
    };
  } catch {
    return null;
  }
}

export function writeSystemUpdateCheckCache(entry: SystemUpdateCheckCacheEntry): void {
  if (typeof window === 'undefined') {
    return;
  }
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(entry));
  } catch {
    /* quota / private mode */
  }
}

export function clearSystemUpdateCheckCache(): void {
  if (typeof window === 'undefined') {
    return;
  }
  try {
    window.localStorage.removeItem(STORAGE_KEY);
  } catch {
    /* ignore */
  }
}

/** @param intervalHours 0 = never treat cache as stale for auto refresh (manual only). */
export function isSystemUpdateCacheFresh(checkedAt: number, intervalHours: number): boolean {
  if (intervalHours <= 0) {
    return true;
  }
  const maxAgeMs = intervalHours * 60 * 60 * 1000;
  return Date.now() - checkedAt < maxAgeMs;
}

/** Whether this browser tab session already ran the one-shot auto remote check. */
export function hasSystemUpdateSessionAutoCheck(): boolean {
  if (typeof window === 'undefined') {
    return true;
  }
  try {
    return window.sessionStorage.getItem(SESSION_AUTO_KEY) === '1';
  } catch {
    return true;
  }
}

export function markSystemUpdateSessionAutoCheck(): void {
  if (typeof window === 'undefined') {
    return;
  }
  try {
    window.sessionStorage.setItem(SESSION_AUTO_KEY, '1');
  } catch {
    /* ignore */
  }
}

export function clearSystemUpdateSessionAutoCheck(): void {
  if (typeof window === 'undefined') {
    return;
  }
  try {
    window.sessionStorage.removeItem(SESSION_AUTO_KEY);
  } catch {
    /* ignore */
  }
}

export function normalizeRemoteCheckIntervalHours(value: unknown): number {
  const n = typeof value === 'number' ? value : Number(value);
  if (!Number.isFinite(n)) {
    return 0;
  }
  return Math.min(168, Math.max(0, Math.round(n)));
}
