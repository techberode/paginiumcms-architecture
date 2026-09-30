const STORAGE_KEY = 'paginium.editor.insertModalWorkspace';

/** Per-browser preference: visual insert modal vs quick Markdown insert. */
export function readInsertModalWorkspace(defaultWhenUnset: boolean): boolean {
  if (typeof window === 'undefined') {
    return defaultWhenUnset;
  }

  const raw = window.localStorage.getItem(STORAGE_KEY);
  if (raw === '0') {
    return false;
  }
  if (raw === '1') {
    return true;
  }

  return defaultWhenUnset;
}

export function writeInsertModalWorkspace(enabled: boolean): void {
  if (typeof window === 'undefined') {
    return;
  }

  window.localStorage.setItem(STORAGE_KEY, enabled ? '1' : '0');
}
