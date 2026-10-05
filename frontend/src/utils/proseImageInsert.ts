import {
  normalizeMediaCaptionPosition,
  proseFigureClassNames,
  type MediaCaptionPosition,
} from './mediaCaption';
import {
  buildProseImageDataAttrs,
  proseImageRequiresHtmlMarkup,
  type ProseImageLightboxExtras,
} from './proseImageAttrs';

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
  captionPosition: MediaCaptionPosition = 'below',
  lightboxExtras?: ProseImageLightboxExtras
): string {
  const displayCaption = caption?.trim() ?? '';
  const shortAlt = alt.trim() || displayCaption || 'Image';
  const position = normalizeMediaCaptionPosition(captionPosition);
  const dataAttrs = buildProseImageDataAttrs(openInLightbox, lightboxExtras);

  if (displayCaption === '') {
    if (openInLightbox && !proseImageRequiresHtmlMarkup(openInLightbox, undefined, lightboxExtras)) {
      return `\n\n![${shortAlt}](${url})\n`;
    }

    return `\n\n<img src="${escapeAttr(url)}" alt="${escapeAttr(shortAlt)}" class="max-w-full h-auto rounded-lg"${dataAttrs} />\n`;
  }

  const figureClass = proseFigureClassNames({ captionPosition: position });
  const imgLine = `<img src="${escapeAttr(url)}" alt="${escapeAttr(shortAlt)}" class="max-w-full h-auto rounded-lg"${dataAttrs} />\n`;
  const capLine = `<figcaption>${escapeHtml(displayCaption)}</figcaption>\n`;
  const body = position === 'above' ? capLine + imgLine : imgLine + capLine;

  return `\n\n<figure class="${figureClass}">\n${body}</figure>\n`;
}
