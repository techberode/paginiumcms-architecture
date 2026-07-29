import { describe, it, expect } from 'vitest';
import { parseSocialLinksJson, serializeSocialLinksJson, defaultSocialLinks } from './socialLinkIcons';

describe('socialLinkIcons', () => {
  it('parses and serializes links', () => {
    const links = defaultSocialLinks();
    const json = serializeSocialLinksJson(links);
    const parsed = parseSocialLinksJson(json);
    expect(parsed[0]?.platform).toBe('github');
  });

  it('returns empty array for invalid json', () => {
    expect(parseSocialLinksJson('{bad')).toEqual([]);
  });
});
