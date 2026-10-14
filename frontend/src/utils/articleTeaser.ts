import type { Article } from '../api/types';

const DEFAULT_MAX_CHARS = 480;
const COLLAPSED_MAX_CHARS = 260;

function stripMarkdownToPlain(raw: string): string {
  let text = raw.trim();
  if (text === '') {
    return '';
  }

  text = text.replace(/```[\s\S]*?```/g, ' ');
  text = text.replace(/`[^`]*`/g, ' ');
  text = text.replace(/!\[[^\]]*]\([^)]*\)/g, ' ');
  text = text.replace(/\[([^\]]+)]\([^)]*\)/g, '$1');
  text = text.replace(/\*\*([^*]+)\*\*/g, '$1');
  text = text.replace(/\*([^*]+)\*/g, '$1');
  text = text.replace(/__([^_]+)__/g, '$1');
  text = text.replace(/_([^_]+)_/g, '$1');
  text = text.replace(/^#{1,6}\s+/gm, '');
  text = text.replace(/^\s*[-*+]\s+/gm, '');
  text = text.replace(/^\s*\d+\.\s+/gm, '');
  text = text.replace(/<[^>]+>/g, ' ');
  text = text.replace(/\s+/g, ' ').trim();

  return text;
}

function trimToLength(text: string, maxChars: number): string {
  if (text.length <= maxChars) {
    return text;
  }

  const slice = text.slice(0, maxChars);
  const lastSpace = slice.lastIndexOf(' ');
  const trimmed = lastSpace > maxChars * 0.6 ? slice.slice(0, lastSpace) : slice;

  return `${trimmed.trim()}…`;
}

/**
 * Public blog hook: prefer a slice of article body, fall back to excerpt / description.
 */
export function buildArticleTeaser(article: Article, maxChars: number = DEFAULT_MAX_CHARS): string {
  const fromBody = stripMarkdownToPlain(article.content || '');
  const bodyTeaser = trimToLength(fromBody, maxChars);
  if (bodyTeaser.length >= 72) {
    return bodyTeaser;
  }

  const fromMeta = String(article.excerpt || (article.frontMatter?.description ?? '')).trim();
  if (fromMeta !== '') {
    return trimToLength(fromMeta, maxChars);
  }

  return bodyTeaser;
}

export function buildArticleTeaserCollapsed(article: Article): string {
  return buildArticleTeaser(article, COLLAPSED_MAX_CHARS);
}
