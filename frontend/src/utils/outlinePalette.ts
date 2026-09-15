import { buildShortcodeSampleMarkup } from './shortcodeSampleMarkup';
import {
  parsePageOutline,
  type CalloutOutlineType,
  type OutlineBlock,
} from './pageOutline';

/** Top-level amateur palette (nested cards stay in innerMarkdown). */
export const OUTLINE_PALETTE_SHORTCODES = [
  'landing-hero',
  'showcase-hero',
  'section-head',
  'feature-grid',
  'cta-banner',
  'testimonial',
  'stats-row',
  'pricing-table',
  'alert-box',
  'coming-soon',
  'stack-grid',
] as const;

export type OutlinePaletteShortcode = (typeof OUTLINE_PALETTE_SHORTCODES)[number];

export function newOutlineId(): string {
  return `outline-${crypto.randomUUID()}`;
}

export function isOutlinePaletteShortcode(name: string): name is OutlinePaletteShortcode {
  return (OUTLINE_PALETTE_SHORTCODES as readonly string[]).includes(name);
}

export function createPaletteShortcode(name: string): OutlineBlock {
  const parsed = parsePageOutline(buildShortcodeSampleMarkup(name));
  const block = parsed[0];
  if (block === undefined || block.kind !== 'shortcode') {
    return {
      id: newOutlineId(),
      kind: 'shortcode',
      name,
      attrs: {},
      innerMarkdown: '',
      selfClosing: true,
    };
  }

  return { ...block, id: newOutlineId() };
}

export function createPaletteMarkdown(placeholder: string): OutlineBlock {
  return { id: newOutlineId(), kind: 'markdown', body: placeholder };
}

export function createPaletteVideo(): OutlineBlock {
  return { id: newOutlineId(), kind: 'video', src: '', poster: '' };
}

export function createPaletteCallout(calloutType: CalloutOutlineType): OutlineBlock {
  return { id: newOutlineId(), kind: 'callout', calloutType, body: '' };
}
