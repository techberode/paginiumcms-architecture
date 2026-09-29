import { describe, expect, it } from 'vitest';
import {
  buildEmbedShortcode,
  expandEmbedShortcodes,
  normalizeEmbedVideoId,
  parseYoutubeVideoId,
  promoteStandaloneVideoUrls,
} from './embedShortcode';

describe('embedShortcode', () => {
  it('parses youtube watch URL to video id', () => {
    expect(parseYoutubeVideoId('https://www.youtube.com/watch?v=2PuFyjAs7JA')).toBe('2PuFyjAs7JA');
    expect(normalizeEmbedVideoId('youtube', 'https://youtu.be/2PuFyjAs7JA')).toBe('2PuFyjAs7JA');
  });

  it('builds shortcode from full youtube URL', () => {
    const block = buildEmbedShortcode('youtube', 'https://www.youtube.com/watch?v=2PuFyjAs7JA');
    expect(block).toContain('id: 2PuFyjAs7JA');
  });

  it('promotes standalone youtube line to iframe html', () => {
    const html = expandEmbedShortcodes('https://www.youtube.com/watch?v=2PuFyjAs7JA');
    expect(html).toContain('youtube-nocookie.com/embed/2PuFyjAs7JA');
  });

  it('does not promote youtube URL mid-paragraph', () => {
    const md = 'Pozri https://www.youtube.com/watch?v=2PuFyjAs7JA tu.';
    expect(promoteStandaloneVideoUrls(md)).toBe(md);
  });
});
