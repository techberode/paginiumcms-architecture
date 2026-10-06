export interface FaqItemPair {
  question: string;
  answer: string;
}

export function parseFaqItems(raw: string): FaqItemPair[] {
  const segments = raw.split(/\s*\|\s*/);
  const pairs: FaqItemPair[] = [];
  for (const segment of segments) {
    const text = segment.trim();
    if (text === '') {
      continue;
    }
    const splitAt = text.indexOf('::');
    if (splitAt === -1) {
      continue;
    }
    const question = text.slice(0, splitAt).trim();
    const answer = text.slice(splitAt + 2).trim();
    if (question === '') {
      continue;
    }
    pairs.push({ question, answer });
  }
  return pairs;
}

export function serializeFaqItems(pairs: FaqItemPair[]): string {
  return pairs
    .filter((pair) => pair.question.trim() !== '')
    .map((pair) => `${pair.question.trim()}::${pair.answer.trim()}`)
    .join(' | ');
}

export function defaultFaqItems(): FaqItemPair[] {
  return parseFaqItems(
    'What is PaginiumCMS?::A hybrid flat-file CMS with a React admin SPA and public site — content lives as UTF-8 files, not in SQL.'
      + ' | Do I need a database?::No. Pages, articles, settings, and media metadata are stored on disk; optional index/cache layers speed up reads.'
      + ' | Is it safe to self-host?::Yes. CSRF on mutating APIs, RBAC, upload allow-lists, CodePolicy for extensions, and encrypted secrets at rest are part of the baseline.'
  );
}
