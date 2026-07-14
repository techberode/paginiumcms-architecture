// frontend/src/hooks/useContentLock.ts
// === Hook: useContentLock (Iterácia 1) ===
// Spravuje životný cyklus zámku dokumentu na frontende:
//  1. pri otvorení (mount) sa pokúsi získať zámok,
//  2. každých 30 s posiela heartbeat na predĺženie,
//  3. pri odchode (unmount / zatvorenie okna) zámok uvoľní.
import { useCallback, useEffect, useRef, useState } from 'react';
import {
  acquireLock,
  releaseLock,
  sendHeartbeat,
  type ContentLock,
} from '../api/locks';

/** Interval heartbeatu podľa špecifikácie: 30 sekúnd. */
const HEARTBEAT_INTERVAL_MS = 30_000;

export type LockStatus =
  | 'idle'
  | 'loading'
  | 'locked-by-me'
  | 'locked-by-other'
  | 'error';

export interface UseContentLockResult {
  status: LockStatus;
  lock: ContentLock | null;
  error: string | null;
  /** Manuálne uvoľnenie (napr. tlačidlo "Zrušiť úpravy"). */
  release: () => Promise<void>;
  /** Nový pokus o získanie zámku (napr. po tom, čo iný používateľ skončil). */
  retry: () => Promise<void>;
}

/**
 * @param resourceId Identifikátor zdroja, napr. `page:o-nas` alebo `article:novinka`.
 * @param enabled   Ak false, hook nič nerobí (napr. kým sa nenačíta obsah).
 */
export function useContentLock(resourceId: string, enabled = true): UseContentLockResult {
  const [status, setStatus] = useState<LockStatus>('idle');
  const [lock, setLock] = useState<ContentLock | null>(null);
  const [error, setError] = useState<string | null>(null);

  // Token vlastníka držíme v ref (nemá spôsobovať re-render a musí byť čerstvý v interval callbacku).
  const tokenRef = useRef<string | null>(null);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const activeRef = useRef(true);

  const stopHeartbeat = useCallback(() => {
    if (intervalRef.current !== null) {
      clearInterval(intervalRef.current);
      intervalRef.current = null;
    }
  }, []);

  // === Blok: Uvoľnenie zámku ===
  const release = useCallback(async () => {
    stopHeartbeat();
    const token = tokenRef.current;
    tokenRef.current = null;
    if (token && resourceId) {
      await releaseLock(resourceId, token);
    }
    if (activeRef.current) {
      setStatus('idle');
      setLock(null);
    }
  }, [resourceId, stopHeartbeat]);

  // === Blok: Heartbeat slučka ===
  const startHeartbeat = useCallback(() => {
    stopHeartbeat();
    intervalRef.current = setInterval(async () => {
      const token = tokenRef.current;
      if (!token) {
        return;
      }
      const outcome = await sendHeartbeat(resourceId, token);
      if (!activeRef.current) {
        return;
      }
      if (outcome.status === 'alive') {
        setLock(outcome.lock);
      } else if (outcome.status === 'conflict') {
        // Zámok sme stratili (expiroval alebo bol prevzatý) – prestaneme tĺcť.
        stopHeartbeat();
        tokenRef.current = null;
        setStatus('locked-by-other');
        setLock(outcome.lock);
        setError(outcome.error);
      }
    }, HEARTBEAT_INTERVAL_MS);
  }, [resourceId, stopHeartbeat]);

  // === Blok: Získanie zámku ===
  const acquire = useCallback(async () => {
    if (!resourceId) {
      return;
    }
    setStatus('loading');
    setError(null);

    const outcome = await acquireLock(resourceId);
    if (!activeRef.current) {
      return;
    }

    switch (outcome.status) {
      case 'acquired':
        tokenRef.current = outcome.token;
        setLock(outcome.lock);
        setStatus('locked-by-me');
        startHeartbeat();
        break;
      case 'conflict':
        setLock(outcome.lock);
        setStatus('locked-by-other');
        setError(outcome.error);
        break;
      default:
        setStatus('error');
        setError(outcome.error);
        break;
    }
  }, [resourceId, startHeartbeat]);

  // === Blok: Životný cyklus (mount / unmount / zatvorenie okna) ===
  useEffect(() => {
    activeRef.current = true;

    if (!enabled || !resourceId) {
      return;
    }

    void acquire();

    // Pri zatvorení karty sa pokúsime zámok uvoľniť "best-effort".
    const handleBeforeUnload = () => {
      const token = tokenRef.current;
      if (token && navigator.sendBeacon) {
        const blob = new Blob([JSON.stringify({ resourceId, token })], { type: 'application/json' });
        navigator.sendBeacon('/api/locks/release', blob);
      }
    };
    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      activeRef.current = false;
      window.removeEventListener('beforeunload', handleBeforeUnload);
      stopHeartbeat();
      const token = tokenRef.current;
      tokenRef.current = null;
      if (token && resourceId) {
        void releaseLock(resourceId, token);
      }
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [resourceId, enabled]);

  return { status, lock, error, release, retry: acquire };
}

export default useContentLock;
