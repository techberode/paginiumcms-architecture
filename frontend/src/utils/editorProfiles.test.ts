import { describe, expect, it } from 'vitest';
import {
  BUILTIN_EDITOR_PROFILES,
  countMarkdownToolbarActions,
  countWysiwygToolbarActions,
  getEditorProfile,
  profileAllows,
  resolveDefaultProfileId,
} from './editorProfiles';

describe('editorProfiles', () => {
  it('exposes at least three built-in profiles', () => {
    expect(BUILTIN_EDITOR_PROFILES.length).toBeGreaterThanOrEqual(3);
  });

  it('resolves defaults by content type', () => {
    expect(resolveDefaultProfileId('page')).toBe('company');
    expect(resolveDefaultProfileId('article')).toBe('blog');
  });

  it('minimal profile disables images', () => {
    const minimal = getEditorProfile('minimal');
    expect(profileAllows(minimal, 'image')).toBe(false);
    expect(profileAllows(minimal, 'bold')).toBe(true);
  });

  it('renders fewer toolbar actions for minimal than developer (markdown)', () => {
    const minimal = getEditorProfile('minimal');
    const developer = getEditorProfile('developer');
    expect(countMarkdownToolbarActions(minimal)).toBeLessThan(countMarkdownToolbarActions(developer));
  });

  it('renders fewer toolbar actions for company than developer (wysiwyg)', () => {
    const company = getEditorProfile('company');
    const developer = getEditorProfile('developer');
    expect(countWysiwygToolbarActions(company)).toBeLessThan(countWysiwygToolbarActions(developer));
  });
});
