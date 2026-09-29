import { useEffect, useState } from 'react';

/**
 * Flips to true after the browser has a chance to paint (idle or next task).
 * Use to defer non-critical API work so the shell can render first.
 */
export function useDeferredAfterPaint(enabled: boolean): boolean {
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!enabled) {
      setReady(false);
      return;
    }

    let cancelled = false;
    const run = () => {
      if (!cancelled) {
        setReady(true);
      }
    };

    if (typeof requestIdleCallback === 'function') {
      const id = requestIdleCallback(run, { timeout: 200 });
      return () => {
        cancelled = true;
        cancelIdleCallback(id);
      };
    }

    const id = window.setTimeout(run, 0);
    return () => {
      cancelled = true;
      window.clearTimeout(id);
    };
  }, [enabled]);

  return ready;
}
