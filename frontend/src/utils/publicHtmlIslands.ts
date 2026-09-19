export type PublicHtmlPart =
  | { kind: 'html'; html: string }
  | { kind: 'gallery'; tag: string; title: string }
  | { kind: 'staff'; mode: string; user: string; type: string; team: string };

const ISLAND =
  /<section\b(?=[^>]*\b(?:pg-feature-gallery|pg-staff-cards)\b)[^>]*>[\s\S]*?<\/section>/gi;

export function splitPublicHtmlIslands(html: string): PublicHtmlPart[] {
  if (html === '') {
    return [{ kind: 'html', html: '' }];
  }

  const parts: PublicHtmlPart[] = [];
  const matcher = new RegExp(ISLAND.source, ISLAND.flags);
  let last = 0;
  let match: RegExpExecArray | null = matcher.exec(html);

  while (match !== null) {
    if (match.index > last) {
      parts.push({ kind: 'html', html: html.slice(last, match.index) });
    }

    const open = match[0].match(/^<section\b[^>]*>/i)?.[0] ?? '';
    if (/\bpg-staff-cards\b/.test(open)) {
      parts.push({
        kind: 'staff',
        mode: readAttr(open, 'data-staff-mode'),
        user: readAttr(open, 'data-staff-user'),
        type: readAttr(open, 'data-staff-type'),
        team: readAttr(open, 'data-staff-team'),
      });
    } else {
      parts.push({
        kind: 'gallery',
        tag: readAttr(open, 'data-tag'),
        title: readAttr(open, 'data-title'),
      });
    }

    last = match.index + match[0].length;
    match = matcher.exec(html);
  }

  if (last < html.length) {
    parts.push({ kind: 'html', html: html.slice(last) });
  }

  return parts.length > 0 ? parts : [{ kind: 'html', html }];
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
