import { describe, expect, it } from 'vitest';
import { applyNormalizedThemeFiles } from './themeStudioNormalize';
import type { ThemeFileListItem } from '../api/themes';

function item(relativePath: string, extra?: Partial<ThemeFileListItem>): ThemeFileListItem {
  return {
    relativePath,
    language: 'plaintext',
    tab: 'other',
    size: 1,
    tooLarge: false,
    ...extra,
  };
}

describe('applyNormalizedThemeFiles', () => {
  it('replaces layout buffers and drops leftover JS when the package has none', () => {
    const { files, buffers } = applyNormalizedThemeFiles(
      [
        item('templates/default.html', { tab: 'html', language: 'html' }),
        item('assets/theme.js', { tab: 'js', language: 'javascript' }),
        item('README.md', { tab: 'other', language: 'markdown' }),
      ],
      {
        'templates/default.html': '<script>alert(1)</script>',
        'assets/theme.js': 'eval(1)',
        'README.md': 'docs',
      },
      {
        'templates/default.html': '<main>{{content}}</main>',
        'assets/theme.css': 'body{margin:0}',
      },
    );

    expect(buffers['templates/default.html']).toBe('<main>{{content}}</main>');
    expect(buffers['assets/theme.css']).toBe('body{margin:0}');
    expect(buffers['assets/theme.js']).toBeUndefined();
    expect(buffers['README.md']).toBe('docs');
    expect(files.map((file) => file.relativePath)).toEqual([
      'assets/theme.css',
      'README.md',
      'templates/default.html',
    ]);
  });
});
