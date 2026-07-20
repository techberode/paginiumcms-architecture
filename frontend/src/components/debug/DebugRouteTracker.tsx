// frontend/src/components/debug/DebugRouteTracker.tsx
import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { debugLog } from '../../utils/debugLog';

export const DebugRouteTracker: React.FC = () => {
  const location = useLocation();
  const previous = useRef(location.pathname);
  const startedAt = useRef<number | null>(null);

  useEffect(() => {
    if (previous.current !== location.pathname) {
      const now = performance.now();
      const durationMs =
        startedAt.current !== null ? Math.round(now - startedAt.current) : undefined;

      debugLog('router.navigate', {
        from: previous.current,
        to: location.pathname,
        search: location.search,
        hash: location.hash,
        durationMs,
      });
      previous.current = location.pathname;
      startedAt.current = now;
    }
  }, [location]);

  return null;
};

export default DebugRouteTracker;
