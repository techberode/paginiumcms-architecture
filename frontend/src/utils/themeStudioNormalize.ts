import type { ThemeFileListItem } from '../api/themes';
import { languageForThemePath, tabForThemePath } from './themeStudioFiles';

export function applyNormalizedThemeFiles(
  currentFiles: ThemeFileListItem[],
  currentBuffers: Record<string, string>,
  normalized: Record<string, string>,
): { files: ThemeFileListItem[]; buffers: Record<string, string> } {
  const buffers: Record<string, string> = { ...currentBuffers, ...normalized };
  const jsKept = Object.keys(normalized).some((path) => path.toLowerCase().endsWith('.js'));
  if (!jsKept) {
    for (const path of Object.keys(buffers)) {
      if (path.toLowerCase().endsWith('.js')) {
        delete buffers[path];
      }
    }
  }

  const byPath = new Map(currentFiles.map((file) => [file.relativePath, file]));
  for (const [path, content] of Object.entries(normalized)) {
    byPath.set(path, {
      relativePath: path,
      language: languageForThemePath(path),
      tab: tabForThemePath(path),
      size: content.length,
      tooLarge: false,
    });
  }

  let files = [...byPath.values()];
  if (!jsKept) {
    files = files.filter((file) => !file.relativePath.toLowerCase().endsWith('.js'));
  }
  files.sort((a, b) => a.relativePath.localeCompare(b.relativePath));

  return { files, buffers };
}
