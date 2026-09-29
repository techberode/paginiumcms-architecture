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

function escapeHtml(text: string): string {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function renderVideoHtml(src: string, poster?: string, caption?: string): string {
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

  return `<figure class="paginium-figure paginium-figure--video">${video}<figcaption>${escapeHtml(cap)}</figcaption></figure>`;
}

export function expandVideoShortcodes(markdown: string): string {
  let result = markdown.replace(
    /:::video\s*\n\s*src:\s*(\S+)(?:\n\s*poster:\s*(\S+))?(?:\n\s*caption:\s*(.+))?\s*\n\s*:::/gs,
    (_, src: string, poster?: string, caption?: string) => renderVideoHtml(src, poster, caption?.trim())
  );

  result = result.replace(
    /:::video\s+src="([^"]+)"(?:\s+poster="([^"]+)")?(?:\s+caption="([^"]*)")?\s*:::/g,
    (_, src: string, poster?: string, caption?: string) => renderVideoHtml(src, poster, caption)
  );

  return result;
}

export function buildVideoShortcode(src: string, poster?: string, caption?: string): string {
  const lines = [':::video', `src: ${src}`];
  if (poster && poster.trim() !== '') {
    lines.push(`poster: ${poster.trim()}`);
  }
  if (caption && caption.trim() !== '') {
    lines.push(`caption: ${caption.trim()}`);
  }
  lines.push(':::');

  return `\n\n${lines.join('\n')}\n`;
}
