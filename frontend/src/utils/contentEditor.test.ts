import { describe, expect, it } from 'vitest';
import {
  convertForModeSwitch,
  htmlToMarkdown,
  inferContentFormat,
  looksLikeHtml,
  looksLikeTiptapJson,
  markdownToHtml,
  storagePayloadFromEditor,
  wrapSelection,
} from './contentEditor';

describe('contentEditor', () => {
  it('detects html content', () => {
    expect(looksLikeHtml('<p>Hello</p>')).toBe(true);
    expect(looksLikeHtml('# Hello')).toBe(false);
  });

  it('detects tiptap json content', () => {
    expect(looksLikeTiptapJson('{"type":"doc","content":[]}')).toBe(true);
    expect(looksLikeTiptapJson('<p>x</p>')).toBe(false);
  });

  it('converts markdown to html', () => {
    expect(markdownToHtml('**bold**')).toContain('<strong>bold</strong>');
  });

  it('converts html to markdown', () => {
    expect(htmlToMarkdown('<h2>Title</h2><p>Text</p>')).toContain('## Title');
  });

  it('switches markdown to wysiwyg html', () => {
    const out = convertForModeSwitch('Hello **world**', 'markdown', 'wysiwyg');
    expect(out).toContain('<strong>world</strong>');
  });

  it('switches wysiwyg html to markdown', () => {
    const out = convertForModeSwitch('<p>Hi</p>', 'wysiwyg', 'markdown');
    expect(out.trim()).toBe('Hi');
  });

  it('infers format from front matter', () => {
    expect(inferContentFormat('plain', 'html')).toBe('html');
    expect(inferContentFormat('{"type":"doc"}', 'tiptap_json')).toBe('tiptap_json');
  });

  it('builds storage payload by mode', () => {
    expect(storagePayloadFromEditor('# x', 'markdown')).toEqual({
      content: '# x',
      contentFormat: 'markdown',
    });
    expect(storagePayloadFromEditor('{"type":"doc","content":[]}', 'wysiwyg')).toEqual({
      content: '{"type":"doc","content":[]}',
      contentFormat: 'tiptap_json',
    });
  });

  it('wraps selection for toolbar', () => {
    const { next, cursor } = wrapSelection('hello world', 6, 11, '**', '**');
    expect(next).toBe('hello **world**');
    expect(cursor).toBe(13);
  });
});
