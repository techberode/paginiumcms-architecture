export type ThemeStudioTab = 'html' | 'css' | 'js' | 'manifest' | 'other';

const TABS: ThemeStudioTab[] = ['html', 'css', 'js', 'manifest', 'other'];

export function languageForThemePath(relativePath: string): string {
  const ext = extensionOf(relativePath);
  if (ext === 'html') {
    return 'html';
  }
  if (ext === 'css') {
    return 'css';
  }
  if (ext === 'js') {
    return 'javascript';
  }
  if (ext === 'json') {
    return 'json';
  }
  if (ext === 'md') {
    return 'markdown';
  }
  return 'plaintext';
}

export function tabForThemePath(relativePath: string): ThemeStudioTab {
  const ext = extensionOf(relativePath);
  if (ext === 'html') {
    return 'html';
  }
  if (ext === 'css') {
    return 'css';
  }
  if (ext === 'js') {
    return 'js';
  }
  if (ext === 'json') {
    return 'manifest';
  }
  return 'other';
}

export function isThemeStudioTab(value: string): value is ThemeStudioTab {
  return TABS.includes(value as ThemeStudioTab);
}

function extensionOf(relativePath: string): string {
  const slash = relativePath.lastIndexOf('/');
  const name = slash >= 0 ? relativePath.slice(slash + 1) : relativePath;
  const dot = name.lastIndexOf('.');
  if (dot < 0) {
    return '';
  }
  return name.slice(dot + 1).toLowerCase();
}
