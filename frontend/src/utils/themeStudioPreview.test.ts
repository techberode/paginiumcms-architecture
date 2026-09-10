import { describe, expect, it } from 'vitest';
import {
  previewTemplateForPath,
  sandboxAllowsSameOrigin,
  sandboxAllowsScripts,
  THEME_STUDIO_PREVIEW_SANDBOX,
} from './themeStudioPreview';

describe('themeStudioPreview', () => {
  it('uses an empty sandbox without scripts or same-origin', () => {
    expect(THEME_STUDIO_PREVIEW_SANDBOX).toBe('');
    expect(sandboxAllowsSameOrigin(THEME_STUDIO_PREVIEW_SANDBOX)).toBe(false);
    expect(sandboxAllowsScripts(THEME_STUDIO_PREVIEW_SANDBOX)).toBe(false);
  });

  it('previews the current HTML template when one is selected', () => {
    expect(previewTemplateForPath('templates/article.html')).toBe('templates/article.html');
    expect(previewTemplateForPath('assets/theme.css')).toBe('templates/default.html');
  });
});
