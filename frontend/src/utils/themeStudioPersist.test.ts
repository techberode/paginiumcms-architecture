import { describe, expect, it } from 'vitest';
import { persistThemeId, themeIdFromManifestJson } from './themeStudioPersist';

describe('themeStudioPersist', () => {
  it('reads a kebab theme id from theme.json', () => {
    expect(themeIdFromManifestJson('{"id":"studio-demo"}')).toBe('studio-demo');
    expect(themeIdFromManifestJson('{')).toBeNull();
    expect(themeIdFromManifestJson('{"id":"paginium-core"}')).toBeNull();
  });

  it('uses the manifest id for a new draft', () => {
    expect(persistThemeId('new', { 'theme.json': '{"id":"untitled-theme"}' })).toBe('untitled-theme');
  });

  it('rejects a manifest id that does not match the folder', () => {
    expect(persistThemeId('clean-journal', { 'theme.json': '{"id":"other-theme"}' })).toBeNull();
    expect(persistThemeId('clean-journal', { 'theme.json': '{"id":"clean-journal"}' })).toBe('clean-journal');
  });
});
