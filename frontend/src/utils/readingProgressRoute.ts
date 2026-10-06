/**
 * Whether the public reading-progress bar should show for the current route.
 */
export function shouldShowReadingProgress(pathname: string, settingEnabled: boolean | undefined): boolean {
  if (settingEnabled === false) {
    return false;
  }

  const normalized = pathname.replace(/\/+$/, '') || '/';

  if (normalized === '/blog') {
    return false;
  }

  if (/^\/blog\/[^/]+$/.test(normalized)) {
    return true;
  }

  if (normalized === '/') {
    return false;
  }

  const reserved = new Set(['/features', '/cookies']);
  if (reserved.has(normalized)) {
    return false;
  }

  return /^\/[^/]+$/.test(normalized);
}
