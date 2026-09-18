/** Mirrors bundled `document-link` shortcode (It.96e). */

function isAllowedDocumentPath(url: string): boolean {
  if (!url.startsWith('/')) {
    return false;
  }

  return url.startsWith('/storage/') || url.startsWith('/api/media/file/');
}

export function sanitizeDocumentHref(url: string): string {
  const trimmed = url.trim();
  if (trimmed === '') {
    return '';
  }

  if (isAllowedDocumentPath(trimmed)) {
    return trimmed;
  }

  try {
    const parsed = new URL(trimmed, 'http://localhost');
    if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
      return '';
    }

    const pathWithQuery = `${parsed.pathname}${parsed.search}`;
    return isAllowedDocumentPath(pathWithQuery) ? pathWithQuery : '';
  } catch {
    return '';
  }
}

function escapeShortcodeAttr(value: string): string {
  return value.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
}

export function buildDocumentLinkShortcode(href: string, label: string): string {
  const safeHref = sanitizeDocumentHref(href);
  if (safeHref === '') {
    return '';
  }

  const safeLabel = label.trim() !== '' ? label.trim() : 'Download';
  return `\n\n[document-link href="${escapeShortcodeAttr(safeHref)}" label="${escapeShortcodeAttr(safeLabel)}"/]\n\n`;
}
