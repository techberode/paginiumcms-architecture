/** Normalize href values for comparing editor link issues with rendered preview anchors. */
export function normalizePreviewLinkHref(href: string): string {
  const trimmed = href.trim();
  if (trimmed === '' || trimmed.startsWith('#')) {
    return trimmed;
  }

  if (/^https?:\/\//i.test(trimmed)) {
    try {
      const url = new URL(trimmed);
      return normalizePreviewLinkHref(url.pathname + url.search);
    } catch {
      return trimmed.toLowerCase();
    }
  }

  let path = trimmed.replace(/\\/g, '/');
  if (path.startsWith('./')) {
    path = path.slice(2);
  } else if (/^(?:\.\.\/)+/.test(path)) {
    path = path.replace(/^(?:\.\.\/)+/, '');
  }

  path = path.replace(/^\/+/, '');
  if (path === '') {
    return '/';
  }

  return `/${path.toLowerCase().replace(/\/+$/, '')}`;
}

export function markBrokenLinksInPreviewHtml(html: string, brokenUrls: readonly string[]): string {
  if (!html.trim() || brokenUrls.length === 0) {
    return html;
  }

  const broken = new Set(brokenUrls.map(normalizePreviewLinkHref));
  if (broken.size === 0) {
    return html;
  }

  if (typeof DOMParser === 'undefined') {
    return html;
  }

  const doc = new DOMParser().parseFromString(html, 'text/html');
  doc.querySelectorAll('a[href]').forEach((anchor) => {
    const href = anchor.getAttribute('href') ?? '';
    if (broken.has(normalizePreviewLinkHref(href))) {
      anchor.classList.add('pg-link-broken');
    }
  });

  return doc.body.innerHTML;
}
