import {
  buildIslandSectionPattern,
  extractIslandAttrs,
  resolveIslandId,
} from '../islands/publicIslandDefinitions';

export type PublicHtmlPart =
  | { kind: 'html'; html: string }
  | { kind: 'island'; id: string; attrs: Record<string, string> };

export function splitPublicHtmlIslands(html: string): PublicHtmlPart[] {
  if (html === '') {
    return [{ kind: 'html', html: '' }];
  }

  const parts: PublicHtmlPart[] = [];
  const islandSection = buildIslandSectionPattern();
  const matcher = new RegExp(islandSection.source, islandSection.flags);
  let last = 0;
  let match: RegExpExecArray | null = matcher.exec(html);

  while (match !== null) {
    if (match.index > last) {
      parts.push({ kind: 'html', html: html.slice(last, match.index) });
    }

    const open = match[0].match(/^<section\b[^>]*>/i)?.[0] ?? '';
    const islandId = resolveIslandId(open);
    if (islandId !== null) {
      parts.push({
        kind: 'island',
        id: islandId,
        attrs: extractIslandAttrs(open, islandId),
      });
    } else {
      parts.push({ kind: 'html', html: match[0] });
    }

    last = match.index + match[0].length;
    match = matcher.exec(html);
  }

  if (last < html.length) {
    parts.push({ kind: 'html', html: html.slice(last) });
  }

  return parts.length > 0 ? parts : [{ kind: 'html', html }];
}

export function hasPublicHtmlIslands(html: string): boolean {
  return splitPublicHtmlIslands(html).some((part) => part.kind === 'island');
}
