import { marked } from 'marked';
import TurndownService from 'turndown';

marked.setOptions({
  gfm: true,
  breaks: true,
});

export type ContentFormat = 'markdown' | 'html' | 'tiptap_json';
export type EditorMode = 'markdown' | 'wysiwyg';

let turndown: TurndownService | null = null;

function getTurndown(): TurndownService {
  if (!turndown) {
    turndown = new TurndownService({
      headingStyle: 'atx',
      codeBlockStyle: 'fenced',
      bulletListMarker: '-',
    });
  }

  return turndown;
}

export function looksLikeHtml(content: string): boolean {
  const trimmed = content.trim();
  if (!trimmed.startsWith('<')) {
    return false;
  }

  return /<\/?[a-z][\s\S]*>/i.test(trimmed);
}

export function looksLikeTiptapJson(content: string): boolean {
  const trimmed = content.trim();
  if (!trimmed.startsWith('{')) {
    return false;
  }

  try {
    const parsed = JSON.parse(trimmed) as { type?: string };
    return parsed.type === 'doc';
  } catch {
    return false;
  }
}

export function inferContentFormat(content: string, frontMatterFormat?: unknown): ContentFormat {
  if (
    frontMatterFormat === 'html' ||
    frontMatterFormat === 'markdown' ||
    frontMatterFormat === 'tiptap_json'
  ) {
    return frontMatterFormat;
  }

  if (looksLikeTiptapJson(content)) {
    return 'tiptap_json';
  }

  return looksLikeHtml(content) ? 'html' : 'markdown';
}

export function parseTiptapDocument(value: string): Record<string, unknown> | string {
  if (!looksLikeTiptapJson(value)) {
    return value;
  }

  try {
    return JSON.parse(value) as Record<string, unknown>;
  } catch {
    return value;
  }
}

export function markdownToHtml(markdown: string): string {
  if (!markdown.trim()) {
    return '';
  }

  return marked.parse(markdown, { async: false }) as string;
}

export function htmlToMarkdown(html: string): string {
  if (!html.trim()) {
    return '';
  }

  return getTurndown().turndown(html);
}

export function valueForEditorMode(
  rawContent: string,
  storedFormat: ContentFormat,
  mode: EditorMode
): string {
  if (mode === 'wysiwyg') {
    if (storedFormat === 'tiptap_json' || storedFormat === 'html') {
      return rawContent;
    }

    return markdownToHtml(rawContent);
  }

  if (storedFormat === 'html') {
    return htmlToMarkdown(rawContent);
  }

  if (storedFormat === 'tiptap_json') {
    return htmlToMarkdown(rawContent);
  }

  return rawContent;
}

export function convertForModeSwitch(
  currentValue: string,
  from: EditorMode,
  to: EditorMode
): string {
  if (from === to) {
    return currentValue;
  }

  if (to === 'wysiwyg') {
    if (looksLikeTiptapJson(currentValue) || looksLikeHtml(currentValue)) {
      return currentValue;
    }

    return markdownToHtml(currentValue);
  }

  if (looksLikeTiptapJson(currentValue)) {
    return currentValue;
  }

  return looksLikeHtml(currentValue) ? htmlToMarkdown(currentValue) : currentValue;
}

export function storagePayloadFromEditor(
  editorValue: string,
  mode: EditorMode
): { content: string; contentFormat: ContentFormat } {
  if (mode === 'wysiwyg') {
    if (looksLikeHtml(editorValue) && !looksLikeTiptapJson(editorValue)) {
      return { content: editorValue, contentFormat: 'html' };
    }

    return { content: editorValue, contentFormat: 'tiptap_json' };
  }

  return {
    content: editorValue,
    contentFormat: 'markdown',
  };
}

export function wrapSelection(
  text: string,
  selectionStart: number,
  selectionEnd: number,
  before: string,
  after: string,
  placeholder = 'text'
): { next: string; cursor: number } {
  const selected = text.slice(selectionStart, selectionEnd) || placeholder;
  const next =
    text.slice(0, selectionStart) + before + selected + after + text.slice(selectionEnd);
  const cursor = selectionStart + before.length + selected.length;

  return { next, cursor };
}

export function insertAtCursor(text: string, selectionStart: number, selectionEnd: number, insert: string): {
  next: string;
  cursor: number;
} {
  const next = text.slice(0, selectionStart) + insert + text.slice(selectionEnd);

  return { next, cursor: selectionStart + insert.length };
}
