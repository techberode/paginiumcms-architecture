import { useEffect, useRef } from 'react';

const BASE_INTERVAL_MS = 30_000;
const BACKOFF_INTERVAL_MS = 120_000;
/** Stop hammering the API (and logs) after repeated desk failures. */
const MAX_FAILURES_BEFORE_PAUSE = 3;

/**
 * Polls {@link load} on an interval. Backs off and pauses after consecutive failures.
 */
export function useDeskPolling(
  enabled: boolean,
  load: () => Promise<boolean>
): void {
  const loadRef = useRef(load);
  loadRef.current = load;
  const failCountRef = useRef(0);
  const pausedRef = useRef(false);

  useEffect(() => {
    if (!enabled) {
      failCountRef.current = 0;
      pausedRef.current = false;
      return;
    }

    let cancelled = false;
    let timer: ReturnType<typeof setTimeout> | undefined;

    const schedule = (delayMs: number) => {
      timer = setTimeout(() => {
        void tick();
      }, delayMs);
    };

    const tick = async () => {
      if (cancelled || pausedRef.current) {
        return;
      }
      const ok = await loadRef.current().catch(() => false);
      if (cancelled) {
        return;
      }
      if (ok) {
        failCountRef.current = 0;
        schedule(BASE_INTERVAL_MS);
        return;
      }
      failCountRef.current += 1;
      if (failCountRef.current >= MAX_FAILURES_BEFORE_PAUSE) {
        pausedRef.current = true;
        return;
      }
      schedule(BACKOFF_INTERVAL_MS);
    };

    void tick();

    return () => {
      cancelled = true;
      if (timer !== undefined) {
        clearTimeout(timer);
      }
    };
  }, [enabled]);
}
