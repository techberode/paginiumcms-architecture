/** Mirrors backend HtmlSafeShortcode for admin Markdown preview (It.91). */

export function buildHtmlSafeShortcode(html: string): string {
  const trimmed = html.trim();
  if (trimmed === '') {
    return '';
  }

  return `\n\n:::html-safe\n${trimmed}\n:::\n`;
}

export function expandHtmlSafeShortcodes(markdown: string): string {
  return markdown.replace(
    /:::html-safe\s*\n([\s\S]*?)\n\s*:::/g,
    (_, body: string) => `<div class="paginium-html-safe">${body.trim()}</div>`
  );
}
