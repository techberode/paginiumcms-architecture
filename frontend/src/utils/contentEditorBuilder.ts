import type { ContentType } from '../api/drafts';
import { normalizeLayoutBuilderMode, type LayoutBuilderMode } from '../layout/pageLayoutTemplates';

export function resolveLayoutBuilderMode(raw: string | undefined | null): LayoutBuilderMode {
  return normalizeLayoutBuilderMode(raw);
}

/** Visual block outline (PageOutlineEditor) — pages and articles when site layout mode is Outline. */
export function contentEditorUsesOutline(type: ContentType, builderMode: LayoutBuilderMode): boolean {
  if (builderMode !== 'outline') {
    return false;
  }

  return type === 'page' || type === 'article';
}

/**
 * Layout shortcode catalog insert — pages in Shortcodes mode; articles in any mode except Outline
 * (outline palette includes feature-gallery and other blocks).
 */
export function contentEditorShowsShortcodePicker(
  type: ContentType,
  builderMode: LayoutBuilderMode
): boolean {
  if (type === 'page') {
    return builderMode === 'shortcodes';
  }

  if (type === 'article') {
    return builderMode !== 'outline';
  }

  return false;
}

/** Site layout template picker — pages only (Templates builder mode). */
export function contentEditorShowsLayoutTemplatePicker(
  type: ContentType,
  builderMode: LayoutBuilderMode
): boolean {
  return type === 'page' && builderMode === 'templates';
}
