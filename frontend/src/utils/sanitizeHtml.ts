import DOMPurify from 'dompurify';

const ALLOWED_TAGS = [
  'p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'img', 'figure', 'figcaption', 'video', 'source', 'blockquote',
  'code', 'pre', 'h1', 'h2', 'h3', 'h4', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
  'div', 'article', 'section', 'aside', 'span', 'footer', 'cite', 'iframe',
];

const ALLOWED_ATTR = [
  'href', 'src', 'alt', 'class', 'title', 'target', 'rel', 'width', 'height',
  'colspan', 'rowspan', 'scope', 'loading', 'decoding', 'cite', 'lang', 'dir', 'role',
  'start', 'type', 'reversed', 'value', 'hreflang', 'controls', 'poster', 'preload', 'playsinline',
  'allowfullscreen', 'referrerpolicy', 'sandbox', 'frameborder',
  'data-tag', 'data-title',
  'data-staff-mode', 'data-staff-user', 'data-staff-type', 'data-staff-team',
  'data-lightbox',
  'data-gallery',
  'data-slideshow',
];

const IFRAME_HOST_SUFFIXES = [
  'youtube.com',
  'youtube-nocookie.com',
  'vimeo.com',
  'player.vimeo.com',
];

let iframeHookInstalled = false;

function isAllowedIframeSrc(src: string): boolean {
  try {
    const url = new URL(src, window.location.origin);
    if (url.protocol !== 'https:') {
      return false;
    }
    const host = url.hostname.toLowerCase();

    return IFRAME_HOST_SUFFIXES.some(
      (suffix) => host === suffix || host.endsWith('.' + suffix)
    );
  } catch {
    return false;
  }
}

function ensureIframeSanitizeHook(): void {
  if (iframeHookInstalled || typeof window === 'undefined') {
    return;
  }

  DOMPurify.addHook('uponSanitizeElement', (node, data) => {
    if (data.tagName !== 'iframe' || !(node instanceof HTMLIFrameElement)) {
      return;
    }

    const src = node.getAttribute('src') ?? '';
    if (src === '' || !isAllowedIframeSrc(src)) {
      node.remove();
      return;
    }

    node.removeAttribute('allow');
    node.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-presentation');
    node.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
  });

  iframeHookInstalled = true;
}

/**
 * Defense-in-depth HTML sanitization before dangerouslySetInnerHTML on public/admin previews.
 * Backend ContentSecuritySanitizer is the primary gate; this catches stale or bypassed HTML.
 */
export function sanitizePublicHtml(html: string): string {
  if (!html.trim()) {
    return '';
  }

  ensureIframeSanitizeHook();

  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS,
    ALLOWED_ATTR,
    ALLOW_DATA_ATTR: false,
  });
}
