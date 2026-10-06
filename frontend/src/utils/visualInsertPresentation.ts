/** Mirrors backend VisualFramePresentation for insert modals. */

import type { VisualMotionDelay, VisualMotionPreset } from '../motion/motionPresets';

export type VisualAlign = 'left' | 'center' | 'right';
export type VisualTextSize = 'sm' | 'md' | 'lg' | 'xl';
export type VisualTone = 'default' | 'muted' | 'primary' | 'danger';
export type VisualMark = 'none' | 'soft' | 'strong';

export interface VisualInsertPresentation {
  align: VisualAlign;
  maxWidth: number;
  textSize: VisualTextSize;
  tone: VisualTone;
  mark: VisualMark;
  bold: boolean;
  italic: boolean;
  underline: boolean;
  motion: VisualMotionPreset;
  motionDelay: VisualMotionDelay;
}

export const VISUAL_MAX_WIDTH_MIN = 280;
export const VISUAL_MAX_WIDTH_MAX = 1280;
export const VISUAL_MAX_WIDTH_DEFAULT = 720;

export const DEFAULT_VISUAL_PRESENTATION: VisualInsertPresentation = {
  align: 'center',
  maxWidth: VISUAL_MAX_WIDTH_DEFAULT,
  textSize: 'md',
  tone: 'default',
  mark: 'none',
  bold: false,
  italic: false,
  underline: false,
  motion: 'none',
  motionDelay: 'normal',
};

export function isDefaultVisualPresentation(presentation: VisualInsertPresentation): boolean {
  return (
    presentation.align === DEFAULT_VISUAL_PRESENTATION.align
    && presentation.maxWidth === DEFAULT_VISUAL_PRESENTATION.maxWidth
    && presentation.textSize === DEFAULT_VISUAL_PRESENTATION.textSize
    && presentation.tone === DEFAULT_VISUAL_PRESENTATION.tone
    && presentation.mark === DEFAULT_VISUAL_PRESENTATION.mark
    && !presentation.bold
    && !presentation.italic
    && !presentation.underline
    && presentation.motion === 'none'
  );
}

export function buildVisualFrameAttrString(presentation: VisualInsertPresentation): string {
  const parts = [
    `align="${presentation.align}"`,
    `max-width="${presentation.maxWidth}"`,
    `text-size="${presentation.textSize}"`,
    `tone="${presentation.tone}"`,
    `mark="${presentation.mark}"`,
    `bold="${presentation.bold ? 'true' : 'false'}"`,
    `italic="${presentation.italic ? 'true' : 'false'}"`,
    `underline="${presentation.underline ? 'true' : 'false'}"`,
  ];

  if (presentation.motion !== 'none') {
    parts.push(`motion="${presentation.motion}"`);
    if (presentation.motionDelay === 'short') {
      parts.push('motion-delay="short"');
    }
  }

  return parts.join(' ');
}

export function wrapWithVisualFrame(innerMarkup: string, presentation: VisualInsertPresentation): string {
  const trimmed = innerMarkup.trim();
  if (trimmed === '' || isDefaultVisualPresentation(presentation)) {
    return trimmed;
  }

  return `[visual-frame ${buildVisualFrameAttrString(presentation)}]\n${trimmed}\n[/visual-frame]`;
}

export function filterShortcodeAttrs(
  attrs: Record<string, string>,
  fieldEnabled: Record<string, boolean>
): Record<string, string> {
  const filtered: Record<string, string> = {};
  for (const [key, value] of Object.entries(attrs)) {
    if (fieldEnabled[key] === false) {
      continue;
    }
    filtered[key] = value;
  }
  return filtered;
}

export function buildShortcodeTagMarkup(
  name: string,
  attrs: Record<string, string>,
  inner: string,
  selfClosing = false
): string {
  const attrString = Object.entries(attrs)
    .filter(([, value]) => value.trim() !== '')
    .map(([key, value]) => `${key}="${value.replace(/"/g, '')}"`)
    .join(' ');

  if (selfClosing) {
    return `[${name}${attrString !== '' ? ` ${attrString}` : ''}/]`;
  }

  return `[${name}${attrString !== '' ? ` ${attrString}` : ''}]${inner}[/${name}]`;
}
