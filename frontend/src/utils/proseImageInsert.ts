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
  caption?: string
): string {
  const displayCaption = caption?.trim() ?? '';
  const shortAlt = alt.trim() || displayCaption || 'Image';

  if (displayCaption === '') {
    if (openInLightbox) {
      return `\n\n![${shortAlt}](${url})\n`;
    }

    return `\n\n<img src="${escapeAttr(url)}" alt="${escapeAttr(shortAlt)}" class="max-w-full h-auto rounded-lg" data-lightbox="off" />\n`;
  }

  const lightboxAttr = openInLightbox ? '' : ' data-lightbox="off"';
  return (
    `\n\n<figure class="paginium-figure">\n` +
    `<img src="${escapeAttr(url)}" alt="${escapeAttr(shortAlt)}" class="max-w-full h-auto rounded-lg"${lightboxAttr} />\n` +
    `<figcaption>${escapeHtml(displayCaption)}</figcaption>\n` +
    `</figure>\n`
  );
}
