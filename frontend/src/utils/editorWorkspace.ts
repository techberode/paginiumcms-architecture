export const EDITOR_WORKSPACE_STORAGE_KEY = 'paginium.editor.workspace';

/** Per-browser override. `null` = follow Settings → Editor default. */
export function readEditorWorkspaceOverride(): boolean | null {
  if (typeof window === 'undefined') {
    return null;
  }

  const raw = window.localStorage.getItem(EDITOR_WORKSPACE_STORAGE_KEY);
  if (raw === 'on') {
    return true;
  }
  if (raw === 'off') {
    return false;
  }

  return null;
}

export function writeEditorWorkspaceOverride(enabled: boolean): void {
  if (typeof window === 'undefined') {
    return;
  }

  window.localStorage.setItem(EDITOR_WORKSPACE_STORAGE_KEY, enabled ? 'on' : 'off');
}

export function resolveEditorWorkspace(settingsDefault: boolean): boolean {
  return readEditorWorkspaceOverride() ?? settingsDefault;
}
