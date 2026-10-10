import { useEffect, useState } from 'react';

/**
 * Flips to true after the browser has a chance to paint (idle or next task).
 * Use to defer non-critical API work so the shell can render first.
 */
export function useDeferredAfterPaint(enabled: boolean, extraDelayMs = 0): boolean {
  const [ready, setReady] = useState(false);

  useEffect(() => {
    if (!enabled) {
      setReady(false);
      return;
    }

    let cancelled = false;
    const markReady = () => {
      if (!cancelled) {
        setReady(true);
      }
    };

    const run = () => {
      if (extraDelayMs > 0) {
        const delayId = window.setTimeout(markReady, extraDelayMs);
        return () => {
          cancelled = true;
          window.clearTimeout(delayId);
        };
      }
      markReady();
      return undefined;
    };

    let cleanupDelay: (() => void) | undefined;

    if (typeof requestIdleCallback === 'function') {
      const id = requestIdleCallback(() => {
        cleanupDelay = run();
      }, { timeout: 200 });
      return () => {
        cancelled = true;
        cancelIdleCallback(id);
        cleanupDelay?.();
      };
    }

    const id = window.setTimeout(() => {
      cleanupDelay = run();
    }, 0);
    return () => {
      cancelled = true;
      window.clearTimeout(id);
      cleanupDelay?.();
    };
  }, [enabled, extraDelayMs]);

  return ready;
}
