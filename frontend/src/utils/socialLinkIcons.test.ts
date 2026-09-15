import { describe, it, expect } from 'vitest';
import { parseSocialLinksJson, serializeSocialLinksJson, defaultSocialLinks, socialBrandColor } from './socialLinkIcons';

describe('socialLinkIcons', () => {
  it('parses and serializes links', () => {
    const links = defaultSocialLinks();
    const json = serializeSocialLinksJson(links);
    const parsed = parseSocialLinksJson(json);
    expect(parsed[0]?.platform).toBe('github');
  });

  it('maps brand colors for well-known networks', () => {
    expect(socialBrandColor('facebook')).toBe('#1877F2');
    expect(socialBrandColor('telegram')).toBe('#26A5E4');
  });

  it('returns empty array for invalid json', () => {
    expect(parseSocialLinksJson('{bad')).toEqual([]);
  });
});
