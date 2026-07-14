// frontend/src/api/locks.ts
// === Locking API (Iterácia 1) ===
// Typované volania na backend /api/locks/*. Backend je jediný zdroj pravdy.
import apiClient from './client';

/**
 * Verejná reprezentácia zámku (bez tokenu – token dostane iba vlastník pri acquire).
 */
export interface ContentLock {
  resourceId: string;
  lockedBy: string;
  lockedByName: string;
  acquiredAt: number;
  lastHeartbeat: number;
  expiresAt: number;
}

export interface AcquireResult {
  lock: ContentLock;
  token: string;
  ttl: number;
}

export interface HeartbeatResult {
  lock: ContentLock;
  ttl: number;
}

/** Spoločné vetvy neúspechu (konflikt / chyba). */
export type LockConflict = { status: 'conflict'; lock: ContentLock | null; error: string };
export type LockError = { status: 'error'; error: string };

/** Výsledok pokusu o získanie zámku. */
export type AcquireOutcome =
  | { status: 'acquired'; lock: ContentLock; token: string; ttl: number }
  | LockConflict
  | LockError;

/** Výsledok heartbeatu. */
export type HeartbeatOutcome =
  | { status: 'alive'; lock: ContentLock; ttl: number }
  | LockConflict
  | LockError;

/**
 * Získa zámok pre daný zdroj. Pri úspechu vráti token pre heartbeat/release.
 */
export async function acquireLock(resourceId: string): Promise<AcquireOutcome> {
  const res = await apiClient.post<AcquireResult>('/api/locks/acquire', { resourceId });

  if (res.success && res.data) {
    return { status: 'acquired', lock: res.data.lock, token: res.data.token, ttl: res.data.ttl };
  }

  if (res.status === 409) {
    return { status: 'conflict', lock: (res.lock as ContentLock) ?? null, error: res.error ?? 'Zdroj je uzamknutý.' };
  }

  return { status: 'error', error: res.error ?? 'Nepodarilo sa získať zámok.' };
}

/**
 * Predĺži zámok (heartbeat). Volá sa periodicky z hooku useContentLock.
 */
export async function sendHeartbeat(resourceId: string, token: string): Promise<HeartbeatOutcome> {
  const res = await apiClient.post<HeartbeatResult>('/api/locks/heartbeat', { resourceId, token });

  if (res.success && res.data) {
    return { status: 'alive', lock: res.data.lock, ttl: res.data.ttl };
  }

  if (res.status === 409) {
    return { status: 'conflict', lock: (res.lock as ContentLock) ?? null, error: res.error ?? 'Zámok už neplatí.' };
  }

  return { status: 'error', error: res.error ?? 'Heartbeat zlyhal.' };
}

/**
 * Uvoľní zámok vlastníka. Bezpečné volať aj keď zámok už neexistuje.
 */
export async function releaseLock(resourceId: string, token: string): Promise<void> {
  await apiClient.post('/api/locks/release', { resourceId, token });
}

/**
 * Zoznam všetkých aktívnych zámkov (admin dashboard – Iterácia 6).
 */
export async function listLocks(): Promise<ContentLock[]> {
  const res = await apiClient.get<ContentLock[]>('/api/locks');
  return res.success && res.data ? res.data : [];
}

/**
 * Vynútené uvoľnenie zámku administrátorom.
 */
export async function forceReleaseLock(resourceId: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/locks/${encodeURIComponent(resourceId)}`);
  return res.success;
}
