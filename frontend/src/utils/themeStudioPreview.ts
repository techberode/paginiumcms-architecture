/** Most restrictive iframe sandbox: no scripts, no same-origin, no forms. */
export const THEME_STUDIO_PREVIEW_SANDBOX = '';

export const THEME_STUDIO_PREVIEW_REFERRER = 'no-referrer' as const;

export function previewTemplateForPath(currentPath: string): string {
  const path = currentPath.trim();
  if (path.startsWith('templates/') && path.toLowerCase().endsWith('.html')) {
    return path;
  }

  return 'templates/default.html';
}

export function sandboxAllowsSameOrigin(sandbox: string): boolean {
  return sandbox.split(/\s+/).includes('allow-same-origin');
}

export function sandboxAllowsScripts(sandbox: string): boolean {
  return sandbox.split(/\s+/).includes('allow-scripts');
}
