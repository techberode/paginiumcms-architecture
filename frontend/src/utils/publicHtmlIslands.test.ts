import { describe, expect, it } from 'vitest';
import { splitPublicHtmlIslands } from './publicHtmlIslands';

describe('splitPublicHtmlIslands', () => {
  it('hydrates staff-card placeholders', () => {
    const html =
      '<p>Before</p><section class="pg-staff-cards" data-staff-mode="user" data-staff-user="ada@example.com" data-staff-type="" data-staff-team=""></section><p>After</p>';
    const parts = splitPublicHtmlIslands(html);
    expect(parts).toEqual([
      { kind: 'html', html: '<p>Before</p>' },
      { kind: 'staff', mode: 'user', user: 'ada@example.com', type: '', team: '' },
      { kind: 'html', html: '<p>After</p>' },
    ]);
  });
});
