import { describe, expect, it } from 'vitest';
import { markBrokenLinksInPreviewHtml, normalizePreviewLinkHref } from './markBrokenLinksInPreviewHtml';

describe('normalizePreviewLinkHref', () => {
  it('normalizes relative article slug', () => {
    expect(normalizePreviewLinkHref('./project-site-planner-test')).toBe('/project-site-planner-test');
  });

  it('normalizes absolute blog path', () => {
    expect(normalizePreviewLinkHref('https://dev.test/blog/foo')).toBe('/blog/foo');
  });
});

describe('markBrokenLinksInPreviewHtml', () => {
  it('adds pg-link-broken to matching anchors', () => {
    const html = '<p><a href="./bad-slug">Link</a> <a href="/blog/ok">OK</a></p>';
    const out = markBrokenLinksInPreviewHtml(html, ['./bad-slug']);
    expect(out).toContain('pg-link-broken');
    expect(out).toContain('href="./bad-slug"');
    expect(out.match(/pg-link-broken/g)?.length).toBe(1);
  });
});
