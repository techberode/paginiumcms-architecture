/** Markdown/HTML snippet for an inline image with optional public lightbox on click. */
export function buildInlineImageMarkup(url: string, alt: string, openInLightbox: boolean): string {
  const safeAlt = alt.replace(/"/g, '&quot;');
  if (openInLightbox) {
    return `\n\n![${alt}](${url})\n`;
  }

  return `\n\n<img src="${url}" alt="${safeAlt}" class="max-w-full h-auto rounded-lg" data-lightbox="off" />\n`;
}
