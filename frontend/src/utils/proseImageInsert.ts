import {
  normalizeMediaCaptionPosition,
  proseFigureClassNames,
  type MediaCaptionPosition,
} from './mediaCaption';

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function escapeAttr(text: string): string {
  return escapeHtml(text);
}

/** Markdown/HTML snippet for an inline image with optional caption and public lightbox on click. */
export function buildInlineImageMarkup(
  url: string,
  alt: string,
  openInLightbox: boolean,
  caption?: string,
  captionPosition: MediaCaptionPosition = 'below'
): string {
  const displayCaption = caption?.trim() ?? '';
  const shortAlt = alt.trim() || displayCaption || 'Image';
  const position = normalizeMediaCaptionPosition(captionPosition);

  if (displayCaption === '') {
    if (openInLightbox) {
      return `\n\n![${shortAlt}](${url})\n`;
    }

    return `\n\n<img src="${escapeAttr(url)}" alt="${escapeAttr(shortAlt)}" class="max-w-full h-auto rounded-lg" data-lightbox="off" />\n`;
  }

  const lightboxAttr = openInLightbox ? '' : ' data-lightbox="off"';
  const figureClass = proseFigureClassNames({ captionPosition: position });
  const imgLine = `<img src="${escapeAttr(url)}" alt="${escapeAttr(shortAlt)}" class="max-w-full h-auto rounded-lg"${lightboxAttr} />\n`;
  const capLine = `<figcaption>${escapeHtml(displayCaption)}</figcaption>\n`;
  const body = position === 'above' ? capLine + imgLine : imgLine + capLine;

  return `\n\n<figure class="${figureClass}">\n${body}</figure>\n`;
}
