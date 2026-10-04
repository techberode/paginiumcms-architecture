import { describe, expect, it } from 'vitest';
import { hasPublicHtmlIslands, splitPublicHtmlIslands } from './publicHtmlIslands';

describe('splitPublicHtmlIslands', () => {
  it('returns plain html when no islands are present', () => {
    expect(splitPublicHtmlIslands('<p>Hello</p>')).toEqual([{ kind: 'html', html: '<p>Hello</p>' }]);
    expect(hasPublicHtmlIslands('<p>Hello</p>')).toBe(false);
  });

  it('hydrates feature-gallery placeholders (legacy class)', () => {
    const html =
      '<p>Before</p><section class="pg-feature-gallery" data-tag="web" data-title="Work"><p>grid</p></section><p>After</p>';
    const parts = splitPublicHtmlIslands(html);
    expect(parts).toEqual([
      { kind: 'html', html: '<p>Before</p>' },
      {
        kind: 'island',
        id: 'feature-gallery',
        attrs: { tag: 'web', title: 'Work', layout: '', columns: '', modalCaptionStyle: '' },
      },
      { kind: 'html', html: '<p>After</p>' },
    ]);
    expect(hasPublicHtmlIslands(html)).toBe(true);
  });

  it('decodes html entities in gallery attrs', () => {
    const html =
      '<section data-title="A &amp; B" class="pg-feature-gallery extra" data-tag="print"></section>';
    expect(splitPublicHtmlIslands(html)).toEqual([
      {
        kind: 'island',
        id: 'feature-gallery',
        attrs: { tag: 'print', title: 'A & B', layout: '', columns: '', modalCaptionStyle: '' },
      },
    ]);
  });

  it('hydrates staff-card placeholders', () => {
    const html =
      '<p>Before</p><section class="pg-staff-cards" data-staff-mode="user" data-staff-user="ada@example.com" data-staff-type="" data-staff-team=""></section><p>After</p>';
    const parts = splitPublicHtmlIslands(html);
    expect(parts).toEqual([
      { kind: 'html', html: '<p>Before</p>' },
      {
        kind: 'island',
        id: 'staff-cards',
        attrs: { mode: 'user', user: 'ada@example.com', type: '', team: '' },
      },
      { kind: 'html', html: '<p>After</p>' },
    ]);
  });

  it('recognizes pg-island--id markers', () => {
    const html =
      '<section class="pg-island pg-island--feature-gallery" data-tag="studio" data-title="Studio"></section>';
    expect(splitPublicHtmlIslands(html)).toEqual([
      {
        kind: 'island',
        id: 'feature-gallery',
        attrs: {
          tag: 'studio',
          title: 'Studio',
          layout: '',
          columns: '',
          modalCaptionStyle: '',
        },
      },
    ]);
  });

  it('hydrates gallery-carousel island markers', () => {
    const html =
      '<section class="pg-gallery-carousel" data-tag="web" data-title="Hi" data-layout="slider" data-effect="subtle" data-autoplay="true"></section>';
    expect(splitPublicHtmlIslands(html)).toEqual([
      {
        kind: 'island',
        id: 'gallery-carousel',
        attrs: {
          tag: 'web',
          title: 'Hi',
          layout: 'slider',
          effect: 'subtle',
          autoplay: 'true',
          modalCaptionStyle: '',
        },
      },
    ]);
  });

  it('prefers data-island when present', () => {
    const html =
      '<section class="pg-island" data-island="staff-cards" data-staff-mode="team" data-staff-team="ops"></section>';
    expect(splitPublicHtmlIslands(html)).toEqual([
      {
        kind: 'island',
        id: 'staff-cards',
        attrs: { mode: 'team', user: '', type: '', team: 'ops' },
      },
    ]);
  });
});
