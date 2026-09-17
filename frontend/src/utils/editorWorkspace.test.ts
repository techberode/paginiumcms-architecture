import { afterEach, describe, expect, it } from 'vitest';
import {
  EDITOR_WORKSPACE_STORAGE_KEY,
  readEditorWorkspaceOverride,
  resolveEditorWorkspace,
  writeEditorWorkspaceOverride,
} from './editorWorkspace';

describe('editorWorkspace', () => {
  afterEach(() => {
    window.localStorage.removeItem(EDITOR_WORKSPACE_STORAGE_KEY);
  });

  it('follows the settings default when the user has no override', () => {
    expect(readEditorWorkspaceOverride()).toBeNull();
    expect(resolveEditorWorkspace(false)).toBe(false);
    expect(resolveEditorWorkspace(true)).toBe(true);
  });

  it('persists an on/off override in localStorage', () => {
    writeEditorWorkspaceOverride(true);
    expect(readEditorWorkspaceOverride()).toBe(true);
    expect(resolveEditorWorkspace(false)).toBe(true);

    writeEditorWorkspaceOverride(false);
    expect(readEditorWorkspaceOverride()).toBe(false);
    expect(resolveEditorWorkspace(true)).toBe(false);
  });
});
