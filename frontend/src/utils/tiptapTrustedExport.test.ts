import { describe, expect, it } from 'vitest';
import { exportTiptapDocToMarkdown, tiptapDocHasTrustedNodes } from './tiptapTrustedExport';

describe('tiptapTrustedExport', () => {
  it('detects trusted nodes', () => {
    const doc = JSON.stringify({
      type: 'doc',
      content: [{ type: 'htmlSafeBlock', attrs: { html: '<div>Hi</div>' } }],
    });
    expect(tiptapDocHasTrustedNodes(doc)).toBe(true);
  });

  it('exports htmlSafeBlock to shortcode', () => {
    const doc = JSON.stringify({
      type: 'doc',
      content: [
        { type: 'paragraph', content: [{ type: 'text', text: 'Intro' }] },
        { type: 'htmlSafeBlock', attrs: { html: '<div>Hi</div>' } },
      ],
    });
    const markdown = exportTiptapDocToMarkdown(doc);
    expect(markdown).toContain('Intro');
    expect(markdown).toContain(':::html-safe');
    expect(markdown).toContain('<div>Hi</div>');
  });

  it('exports external embed to shortcode', () => {
    const doc = JSON.stringify({
      type: 'doc',
      content: [{ type: 'externalEmbed', attrs: { provider: 'youtube', id: 'dQw4w9WgXcQ' } }],
    });
    const markdown = exportTiptapDocToMarkdown(doc);
    expect(markdown).toContain(':::embed');
    expect(markdown).toContain('youtube');
  });
});
