import { describe, expect, it } from 'vitest';
import {
  convertForModeSwitch,
  htmlToMarkdown,
  inferContentFormat,
  looksLikeHtml,
  looksLikeTiptapJson,
  markdownToHtml,
  normalizeLegalMarkdown,
  plainMarkdownToHtml,
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

  it('expands video shortcode in markdown preview', () => {
    const html = markdownToHtml(':::video\nsrc: /storage/app/content/media/x.mp4\n:::');
    expect(html).toContain('<video src="/storage/app/content/media/x.mp4"');
    expect(html).toContain('controls');
  });

  it('plainMarkdownToHtml skips shortcodes', () => {
    const raw = ':::video\nsrc: /storage/app/content/media/x.mp4\n:::';
    expect(plainMarkdownToHtml(raw)).not.toContain('<video');
    expect(plainMarkdownToHtml('**bold**')).toContain('<strong>bold</strong>');
  });

  it('normalizeLegalMarkdown splits single-newline paragraphs for legal paste', () => {
    expect(normalizeLegalMarkdown('Prvý odsek.\nDruhý odsek.')).toBe('Prvý odsek.\n\nDruhý odsek.');
    expect(normalizeLegalMarkdown('## Nadpis\nText pod nadpisom.')).toBe('## Nadpis\n\nText pod nadpisom.');
  });

  it('plainMarkdownToHtml renders separate p tags for single-newline paragraphs', () => {
    const html = plainMarkdownToHtml('Prvý odsek.\nDruhý odsek.');
    expect(html.match(/<p>/g)?.length).toBe(2);
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
