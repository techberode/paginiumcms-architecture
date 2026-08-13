import DOMPurify from 'dompurify';

const ALLOWED_TAGS = [
  'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'blockquote',
  'code', 'pre', 'h1', 'h2', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
  'div', 'article', 'section', 'aside', 'span',
];

const ALLOWED_ATTR = [
  'href', 'src', 'alt', 'class', 'title', 'target', 'rel', 'width', 'height',
  'colspan', 'rowspan', 'scope', 'loading', 'decoding', 'cite', 'lang', 'dir', 'role',
  'start', 'type', 'reversed', 'value', 'hreflang',
];

/**
 * Defense-in-depth HTML sanitization before dangerouslySetInnerHTML on public/admin previews.
 * Backend ContentSecuritySanitizer is the primary gate; this catches stale or bypassed HTML.
 */
export function sanitizePublicHtml(html: string): string {
  if (!html.trim()) {
    return '';
  }

  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    ALLOW_DATA_ATTR: false,
  });
}
