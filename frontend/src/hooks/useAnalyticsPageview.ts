import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { trackPublicPageview } from '../api/analyticsPageview';
import { useCookieConsentOptional } from '../context/CookieConsentContext';

const ADMIN_PREFIXES = [
  '/dashboard',
  '/pages',
  '/articles',
  '/media',
  '/navigation',
  '/comments',
  '/messages',
  '/newsletter',
  '/github',
  '/code-editor',
  '/backups',
  '/trash',
  '/firewall',
  '/logs',
  '/audit',
  '/notifications',
  '/scheduler',
  '/platform',
  '/settings',
  '/translations',
  '/users',
  '/developer',
  '/analytics',
  '/security',
  '/blueprints',
  '/extensions',
  '/demo',
  '/account',
  '/login',
  '/register',
  '/forgot-password',
  '/reset-password',
];

function isTrackablePublicPath(pathname: string): boolean {
  if (pathname.startsWith('/newsletter/') || pathname === '/cookies') {
    return false;
  }

  return !ADMIN_PREFIXES.some((prefix) => pathname === prefix || pathname.startsWith(`${prefix}/`));
}

/**
 * Sends a pageview beacon on public SPA route changes (Iteration 33 fix — static nginx never hit AnalyticsMiddleware).
 */
export function useAnalyticsPageview(): void {
  const { pathname } = useLocation();
  const consent = useCookieConsentOptional();
  const enteredAtRef = useRef(Date.now());
  const previousPathRef = useRef<string | null>(null);

  const analyticsAllowed = consent ? consent.analytics : true;
  const consentDecided = consent ? consent.decided : true;

  useEffect(() => {
    if (!consentDecided || !analyticsAllowed) {
      return;
    }

    if (!isTrackablePublicPath(pathname)) {
      previousPathRef.current = pathname;
      enteredAtRef.current = Date.now();
      return;
    }

    const now = Date.now();
    const previousPath = previousPathRef.current;
    let durationSeconds: number | undefined;
    if (previousPath && isTrackablePublicPath(previousPath)) {
      const elapsed = Math.round((now - enteredAtRef.current) / 1000);
      if (elapsed > 0 && elapsed <= 7200) {
        durationSeconds = elapsed;
      }
    }

    void trackPublicPageview({
      uri: pathname || '/',
      ...(durationSeconds !== undefined ? { duration_seconds: durationSeconds } : {}),
    });

    previousPathRef.current = pathname;
    enteredAtRef.current = now;
  }, [analyticsAllowed, consentDecided, pathname]);
}
