/** Mirrors backend VideoEmbedShortcode for admin Markdown preview (It.79). */

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

function renderVideoHtml(src: string, poster?: string): string {
  const safeSrc = sanitizeMediaUrl(src);
  if (safeSrc === '') {
    return '';
  }

  const safePoster = poster ? sanitizeMediaUrl(poster) : '';
  const posterAttr = safePoster !== '' ? ` poster="${safePoster.replace(/"/g, '&quot;')}"` : '';

  return `<video src="${safeSrc.replace(/"/g, '&quot;')}" controls playsinline preload="metadata"${posterAttr}></video>`;
}

export function expandVideoShortcodes(markdown: string): string {
  let result = markdown.replace(
    /:::video\s*\n\s*src:\s*(\S+)(?:\n\s*poster:\s*(\S+))?\s*\n\s*:::/g,
    (_, src: string, poster?: string) => renderVideoHtml(src, poster)
  );

  result = result.replace(
    /:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?\s*:::/g,
    (_, src: string, poster?: string) => renderVideoHtml(src, poster)
  );

  return result;
}

export function buildVideoShortcode(src: string, poster?: string): string {
  const lines = [':::video', `src: ${src}`];
  if (poster && poster.trim() !== '') {
    lines.push(`poster: ${poster.trim()}`);
  }
  lines.push(':::');

  return `\n\n${lines.join('\n')}\n`;
}
