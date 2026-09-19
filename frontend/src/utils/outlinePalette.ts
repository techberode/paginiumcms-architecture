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
  'feature-gallery',
  'feature-grid',
  'cta-banner',
  'testimonial',
  'stats-row',
  'pricing-table',
  'alert-box',
  'coming-soon',
  'stack-grid',
  'staff-card',
  'staff-team',
] as const;

export type OutlinePaletteShortcode = (typeof OUTLINE_PALETTE_SHORTCODES)[number];

export function newOutlineId(): string {
  try {
    const cryptoObj = globalThis.crypto;
    if (cryptoObj && typeof cryptoObj.randomUUID === 'function') {
      return `outline-${cryptoObj.randomUUID()}`;
    }
  } catch {
    // http://LAN is not a secure context — randomUUID exists but throws.
  }

  return `outline-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
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

/** Spec amateur starter: hero + cards + CTA (still Markdown shortcodes). */
export const OUTLINE_PORTFOLIO_STARTER = ['landing-hero', 'feature-grid', 'cta-banner'] as const;

/** Alternate landing stack: showcase hero + cards + CTA. */
export const OUTLINE_LANDING_STARTER = ['showcase-hero', 'feature-grid', 'cta-banner'] as const;

export type OutlineStarterPackId = 'portfolio' | 'landing';

export function outlineStarterNames(pack: OutlineStarterPackId): readonly string[] {
  return pack === 'landing' ? OUTLINE_LANDING_STARTER : OUTLINE_PORTFOLIO_STARTER;
}

export function createOutlineStarterPack(pack: OutlineStarterPackId = 'portfolio'): OutlineBlock[] {
  return outlineStarterNames(pack).map((name) => createPaletteShortcode(name));
}

export function moveOutlineBlock<T>(items: T[], fromIndex: number, toIndex: number): T[] {
  if (
    fromIndex === toIndex
    || fromIndex < 0
    || toIndex < 0
    || fromIndex >= items.length
    || toIndex >= items.length
  ) {
    return items;
  }

  const next = [...items];
  const [moved] = next.splice(fromIndex, 1);
  if (moved === undefined) {
    return items;
  }

  next.splice(toIndex, 0, moved);
  return next;
}
