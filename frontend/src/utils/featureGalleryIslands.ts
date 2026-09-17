export type FeatureGalleryHtmlPart =
  | { kind: 'html'; html: string }
  | { kind: 'gallery'; tag: string; title: string };

const GALLERY_SECTION =
  /<section\b(?=[^>]*\bpg-feature-gallery\b)[^>]*>[\s\S]*?<\/section>/gi;

/**
 * Split expander HTML so the public SPA can hydrate FeatureGallerySection
 * over each It.65 gallery island (It.58f-f).
 */
export function splitFeatureGalleryHtml(html: string): FeatureGalleryHtmlPart[] {
  if (html === '') {
    return [{ kind: 'html', html: '' }];
  }

  const parts: FeatureGalleryHtmlPart[] = [];
  const matcher = new RegExp(GALLERY_SECTION.source, GALLERY_SECTION.flags);
  let last = 0;
  let match: RegExpExecArray | null = matcher.exec(html);

  while (match !== null) {
    if (match.index > last) {
      parts.push({ kind: 'html', html: html.slice(last, match.index) });
    }

    const open = match[0].match(/^<section\b[^>]*>/i)?.[0] ?? '';
    parts.push({
      kind: 'gallery',
      tag: readAttr(open, 'data-tag'),
      title: readAttr(open, 'data-title'),
    });

    last = match.index + match[0].length;
    match = matcher.exec(html);
  }

  if (last < html.length) {
    parts.push({ kind: 'html', html: html.slice(last) });
  }

  return parts.length > 0 ? parts : [{ kind: 'html', html }];
}

export function hasFeatureGalleryIsland(html: string): boolean {
  return splitFeatureGalleryHtml(html).some((part) => part.kind === 'gallery');
}

function readAttr(openTag: string, name: string): string {
  const double = openTag.match(new RegExp(`\\b${name}\\s*=\\s*"([^"]*)"`, 'i'));
  if (double) {
    return decodeHtmlAttr(double[1] ?? '');
  }

  const single = openTag.match(new RegExp(`\\b${name}\\s*=\\s*'([^']*)'`, 'i'));
  return single ? decodeHtmlAttr(single[1] ?? '') : '';
}

function decodeHtmlAttr(value: string): string {
  return value
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>');
}
