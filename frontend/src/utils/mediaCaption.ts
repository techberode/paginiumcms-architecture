/** Caption placement for prose figures (images + DAM video). */

export type MediaCaptionPosition = 'above' | 'below';

export function normalizeMediaCaptionPosition(value: unknown): MediaCaptionPosition {
  const raw = String(value ?? '').trim().toLowerCase();
  return raw === 'above' ? 'above' : 'below';
}

export function proseFigureClassNames(options: {
  video?: boolean;
  captionPosition?: MediaCaptionPosition;
}): string {
  const parts = ['paginium-figure'];
  if (options.video) {
    parts.push('paginium-figure--video');
  }
  if (options.captionPosition === 'above') {
    parts.push('paginium-figure--caption-top');
  }
  return parts.join(' ');
}
