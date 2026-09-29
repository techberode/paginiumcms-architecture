/** Mirrors backend VideoEmbedShortcode for admin Markdown preview (It.79). */

import {
  normalizeMediaCaptionPosition,
  proseFigureClassNames,
  type MediaCaptionPosition,
} from './mediaCaption';

function isAllowedMediaPath(url: string): boolean {
  if (!url.startsWith('/')) {
    return false;
  }

  return url.startsWith('/storage/') || url.startsWith('/api/media/file/');
}

function sanitizeMediaUrl(url: string): string {
  const trimmed = url.trim();
  if (trimmed === '') {
    return '';
  }

  if (isAllowedMediaPath(trimmed)) {
    return trimmed;
  }

  try {
    const parsed = new URL(trimmed, 'http://localhost');
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
      return '';
    }

    return isAllowedMediaPath(parsed.pathname) ? trimmed : '';
  } catch {
    return '';
  }
}

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function renderVideoHtml(
  src: string,
  poster?: string,
  caption?: string,
  captionPosition: MediaCaptionPosition = 'below'
): string {
  const safeSrc = sanitizeMediaUrl(src);
  if (safeSrc === '') {
    return '';
  }

  const safePoster = poster ? sanitizeMediaUrl(poster) : '';
  const posterAttr = safePoster !== '' ? ` poster="${safePoster.replace(/"/g, '&quot;')}"` : '';
  const video = `<video src="${safeSrc.replace(/"/g, '&quot;')}" controls playsinline preload="metadata"${posterAttr}></video>`;
  const cap = (caption ?? '').trim();
  if (cap === '') {
    return video;
  }

  const position = normalizeMediaCaptionPosition(captionPosition);
  const figureClass = proseFigureClassNames({ video: true, captionPosition: position });
  const capHtml = `<figcaption>${escapeHtml(cap)}</figcaption>`;
  const inner = position === 'above' ? `${capHtml}${video}` : `${video}${capHtml}`;

  return `<figure class="${figureClass}">${inner}</figure>`;
}

const BLOCK_VIDEO =
  /:::video\s*\n\s*src:\s*(\S+)(?:\n\s*poster:\s*(\S+))?(?:\n\s*caption:\s*(.+?))?(?:\n\s*captionPosition:\s*(above|below))?\s*\n\s*:::/gs;

export function expandVideoShortcodes(markdown: string): string {
  let result = markdown.replace(BLOCK_VIDEO, (_, src: string, poster?: string, caption?: string, captionPosition?: string) =>
    renderVideoHtml(src, poster, caption?.trim(), normalizeMediaCaptionPosition(captionPosition))
  );

  result = result.replace(
    /:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?(?:\s+caption="([^"]*)")?(?:\s+captionPosition="(above|below)")?\s*:::/g,
    (_, src: string, poster?: string, caption?: string, captionPosition?: string) =>
      renderVideoHtml(src, poster, caption, normalizeMediaCaptionPosition(captionPosition))
  );

  return result;
}

export function buildVideoShortcode(
  src: string,
  poster?: string,
  caption?: string,
  captionPosition: MediaCaptionPosition = 'below'
): string {
  const lines = [':::video', `src: ${src}`];
  if (poster && poster.trim() !== '') {
    lines.push(`poster: ${poster.trim()}`);
  }
  if (caption && caption.trim() !== '') {
    lines.push(`caption: ${caption.trim()}`);
    const position = normalizeMediaCaptionPosition(captionPosition);
    if (position === 'above') {
      lines.push('captionPosition: above');
    }
  }
  lines.push(':::');

  return `\n\n${lines.join('\n')}\n`;
}
