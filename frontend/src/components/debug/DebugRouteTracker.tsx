// frontend/src/components/debug/DebugRouteTracker.tsx
import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { debugLog } from '../../utils/debugLog';

export const DebugRouteTracker: React.FC = () => {
  const location = useLocation();
  const previous = useRef(location.pathname);

  useEffect(() => {
    if (previous.current !== location.pathname) {
      debugLog('router.navigate', {
        from: previous.current,
        to: location.pathname,
        search: location.search,
        hash: location.hash,
      });
      previous.current = location.pathname;
    }
  }, [location]);

  return null;
};

export default DebugRouteTracker;
