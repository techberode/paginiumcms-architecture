import { describe, expect, it } from 'vitest';
import { clipboardHasRichHtml, decideMarkdownPaste } from './markdownPaste';

function dataTransfer(pairs: Record<string, string>): DataTransfer {
  return {
    getData(type: string) {
      return pairs[type] ?? '';
    },
  } as DataTransfer;
}

describe('markdownPaste', () => {
  it('allows plain markdown with autolink angle brackets', () => {
    const dt = dataTransfer({
      'text/plain': 'See <https://example.com> for details.',
    });
    expect(clipboardHasRichHtml(dt)).toBe(false);
    expect(decideMarkdownPaste(dt)).toEqual({ action: 'default' });
  });

  it('allows plain markdown with inline HTML tags', () => {
    const dt = dataTransfer({
      'text/plain': '<details><summary>More</summary></details>',
    });
    expect(decideMarkdownPaste(dt)).toEqual({ action: 'default' });
  });

  it('inserts plain text when rich HTML is also on the clipboard', () => {
    const dt = dataTransfer({
      'text/html': '<meta charset="utf-8"><p>Hello</p>',
      'text/plain': '# Hello\n\nWorld',
    });
    expect(decideMarkdownPaste(dt)).toEqual({ action: 'insertPlain', text: '# Hello\n\nWorld' });
  });

  it('blocks HTML-only clipboard without plain text', () => {
    const dt = dataTransfer({
      'text/html': '<p><strong>Only HTML</strong></p>',
    });
    expect(decideMarkdownPaste(dt)).toEqual({ action: 'block' });
  });
});
