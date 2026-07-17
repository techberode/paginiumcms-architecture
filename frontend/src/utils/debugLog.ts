// frontend/src/utils/debugLog.ts
import { resolveApiBaseUrl } from './apiBaseUrl';

const DEBUG_ENDPOINT = '/api/debug/client-event';

/** Cesty, ktorých telo sa nikdy neloguje (heslá, tokeny). */
const SENSITIVE_API_PATHS = [
  '/api/auth/login',
  '/api/auth/register',
  '/api/auth/change-password',
  '/api/auth/verify-reset-token',
  '/api/auth/2fa/verify-login',
];

let monitoringInitialized = false;

/** Len pre testy – reset stavu monitorovania. */
export function resetDebugMonitoringForTests(): void {
  monitoringInitialized = false;
}

export function isDebugEnabled(): boolean {
  return import.meta.env.VITE_DEBUG === 'true' || import.meta.env.DEV;
}

function sendToBackend(event: string, context: Record<string, unknown>): void {
  const apiUrl = String(context.url ?? '');
  if (apiUrl.includes(DEBUG_ENDPOINT)) {
    return;
  }

  void fetch(`${resolveApiBaseUrl()}${DEBUG_ENDPOINT}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    credentials: 'include',
    keepalive: true,
    body: JSON.stringify({
      event,
      context,
      url: window.location.href,
      timestamp: new Date().toISOString(),
    }),
  }).catch(() => {
    // Backend môže ešte nabiehať.
  });
}

export function debugLog(event: string, context: Record<string, unknown> = {}): void {
  if (!isDebugEnabled()) {
    return;
  }

  const payload = {
    event,
    context,
    url: window.location.href,
    timestamp: new Date().toISOString(),
  };

  console.debug('[PaginiumCMS]', payload);

  sendToBackend(event, context);
}

export function debugLogProvider(
  provider: string,
  event: string,
  context: Record<string, unknown> = {}
): void {
  debugLog(`provider.${provider}.${event}`, context);
}

export function debugLogApi(
  phase: 'request' | 'response' | 'error',
  method: string,
  url: string,
  extra: Record<string, unknown> = {}
): void {
  if (!isDebugEnabled()) {
    return;
  }

  const sensitive = SENSITIVE_API_PATHS.some((path) => url.includes(path));
  const context: Record<string, unknown> = {
    method: method.toUpperCase(),
    url,
    sensitive,
    ...extra,
  };

  if (sensitive) {
    delete context.data;
    delete context.body;
  }

  debugLog(`api.${phase}`, context);
}

export function initDebugMonitoring(): void {
  if (!isDebugEnabled() || monitoringInitialized) {
    return;
  }
  monitoringInitialized = true;

  window.addEventListener('error', (event) => {
    debugLog('runtime.error', {
      message: event.message,
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno,
    });
  });

  window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason;
    debugLog('runtime.unhandled_rejection', {
      reason: reason instanceof Error ? reason.message : String(reason),
      stack: reason instanceof Error ? reason.stack : undefined,
    });
  });

  window.addEventListener('visibilitychange', () => {
    debugLog('document.visibility', { state: document.visibilityState });
  });

  debugLog('debug.monitoring.initialized', {
    online: navigator.onLine,
    cookieEnabled: navigator.cookieEnabled,
  });
}

export function logFrontendStartup(): void {
  initDebugMonitoring();

  debugLog('frontend.startup', {
    mode: import.meta.env.MODE,
    apiUrl: resolveApiBaseUrl(),
    userAgent: navigator.userAgent,
    language: navigator.language,
    viewport: `${window.innerWidth}x${window.innerHeight}`,
    screen: `${window.screen.width}x${window.screen.height}`,
    referrer: document.referrer || null,
    hash: window.location.hash || null,
  });
}
