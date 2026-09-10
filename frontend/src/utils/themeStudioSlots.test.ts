import { describe, expect, it } from 'vitest';
import { detectThemeStudioSlots, insertIntoMainContent } from './themeStudioSlots';

describe('themeStudioSlots', () => {
  it('detects CMS slot tokens and semantic tags', () => {
    const slots = detectThemeStudioSlots({
      'templates/default.html': '{{> header}}<main>{{content}}</main>{{> footer}}',
      'partials/header.html': '<header></header>',
    });

    expect(slots.find((slot) => slot.id === 'header')?.found).toBe(true);
    expect(slots.find((slot) => slot.id === 'main')?.found).toBe(true);
    expect(slots.find((slot) => slot.id === 'footer')?.found).toBe(true);
    expect(slots.find((slot) => slot.id === 'sidebar')?.found).toBe(false);
    expect(slots.find((slot) => slot.id === 'header')?.sourcePath).toBe('partials/header.html');
  });

  it('inserts bundled shortcode markup before {{content}}', () => {
    const html = '<main>{{content}}</main>';
    expect(insertIntoMainContent(html, '[landing-hero title="Hi"/]')).toBe(
      '<main>[landing-hero title="Hi"/]\n{{content}}</main>',
    );
  });
});
