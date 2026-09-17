import { describe, expect, it, vi } from 'vitest';
import { buildContentPreviewSrcDoc } from './contentPreviewSrcDoc';

/** Vitest `css: false` empties `?raw`; keep the selectors the srcDoc must inline. */
vi.mock('../theme/pgLayout.css?raw', () => ({
  default: '.pg-stats{display:grid}.pg-hero{position:relative}',
}));

describe('buildContentPreviewSrcDoc', () => {
  it('wraps expanded HTML in a UTF-8 document with a tight CSP', () => {
    const doc = buildContentPreviewSrcDoc('<h1>Prehľad</h1>');
    expect(doc).toContain('<meta charset="UTF-8">');
    expect(doc).toContain("default-src 'none'");
    expect(doc).toContain('<h1>Prehľad</h1>');
    expect(doc).not.toContain('allow-scripts');
    expect(doc).toContain('.pg-stats');
    expect(doc).toContain('opacity:1');
    expect(doc).toContain('pg-layout-landing');
  });
});
