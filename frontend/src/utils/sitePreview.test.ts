import { describe, it, expect } from 'vitest';
import { buildSitePreviewDraft, previewFrameMaxWidth } from './sitePreview';

describe('previewFrameMaxWidth', () => {
  it('scales max width proportionally', () => {
    expect(previewFrameMaxWidth('100')).toBe(1200);
    expect(previewFrameMaxWidth('75')).toBe(900);
    expect(previewFrameMaxWidth('50')).toBe(600);
    expect(previewFrameMaxWidth('fullscreen')).toBeNull();
  });
});

describe('buildSitePreviewDraft', () => {
  it('maps article API payload', () => {
    const draft = buildSitePreviewDraft('article', {
      title: 'Test',
      slug: 'test',
      content: '# Hello',
      tags: ['FlatFile'],
      author: 'Max',
    });
    expect(draft.type).toBe('article');
    expect(draft.tags).toEqual(['FlatFile']);
    expect(draft.content).toBe('# Hello');
  });
});
