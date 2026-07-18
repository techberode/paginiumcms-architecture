import { marked } from 'marked';
import TurndownService from 'turndown';

marked.setOptions({
  gfm: true,
  breaks: true,
});

export type ContentFormat = 'markdown' | 'html';
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

export function inferContentFormat(content: string, frontMatterFormat?: unknown): ContentFormat {
  if (frontMatterFormat === 'html' || frontMatterFormat === 'markdown') {
    return frontMatterFormat;
  }

  return looksLikeHtml(content) ? 'html' : 'markdown';
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
    return storedFormat === 'html' ? rawContent : markdownToHtml(rawContent);
  }

  return storedFormat === 'html' ? htmlToMarkdown(rawContent) : rawContent;
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
    return looksLikeHtml(currentValue) ? currentValue : markdownToHtml(currentValue);
  }

  return looksLikeHtml(currentValue) ? htmlToMarkdown(currentValue) : currentValue;
}

export function storagePayloadFromEditor(
  editorValue: string,
  mode: EditorMode
): { content: string; contentFormat: ContentFormat } {
  return {
    content: editorValue,
    contentFormat: mode === 'wysiwyg' ? 'html' : 'markdown',
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
