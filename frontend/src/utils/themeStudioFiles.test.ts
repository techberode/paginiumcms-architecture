import { describe, expect, it } from 'vitest';
import { languageForThemePath, tabForThemePath } from './themeStudioFiles';
import { themeStudioDraftFiles } from './themeStudioDraft';

describe('themeStudioFiles', () => {
  it('maps extensions to Monaco languages and studio tabs', () => {
    expect(languageForThemePath('templates/default.html')).toBe('html');
    expect(tabForThemePath('templates/default.html')).toBe('html');
    expect(languageForThemePath('assets/theme.css')).toBe('css');
    expect(tabForThemePath('assets/theme.css')).toBe('css');
    expect(languageForThemePath('assets/app.js')).toBe('javascript');
    expect(tabForThemePath('assets/app.js')).toBe('js');
    expect(languageForThemePath('theme.json')).toBe('json');
    expect(tabForThemePath('theme.json')).toBe('manifest');
    expect(languageForThemePath('README.md')).toBe('markdown');
    expect(tabForThemePath('README.md')).toBe('other');
  });
});

describe('themeStudioDraft', () => {
  it('includes layout, css, and manifest buffers', () => {
    const files = themeStudioDraftFiles();
    const paths = files.map((file) => file.relativePath);
    expect(paths).toContain('theme.json');
    expect(paths).toContain('templates/default.html');
    expect(paths).toContain('assets/theme.css');
    expect(files.find((file) => file.relativePath === 'theme.json')?.tab).toBe('manifest');
  });
});
