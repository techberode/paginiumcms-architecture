import { describe, expect, it } from 'vitest';
import {
  isBareSocialPrefix,
  socialProfilePrefix,
  suggestSocialProfileUrl,
} from './socialProfileUrl';

describe('socialProfileUrl', () => {
  it('returns the profile prefix for known platforms', () => {
    expect(socialProfilePrefix('github')).toBe('https://github.com/');
    expect(socialProfilePrefix('telegram')).toBe('https://t.me/');
    expect(socialProfilePrefix('email')).toBe('');
  });

  it('treats empty and prefix-only values as bare', () => {
    expect(isBareSocialPrefix('')).toBe(true);
    expect(isBareSocialPrefix('https://github.com/')).toBe(true);
    expect(isBareSocialPrefix('https://github.com/ada')).toBe(false);
  });

  it('fills a prefix when switching platforms or starting empty', () => {
    expect(suggestSocialProfileUrl('github', 'telegram', '')).toBe('https://github.com/');
    expect(suggestSocialProfileUrl('github', 'telegram', '@desk')).toBe('https://github.com/');
    expect(suggestSocialProfileUrl('telegram', 'telegram', '')).toBe('https://t.me/');
  });

  it('keeps an existing account URL when the same platform is clicked again', () => {
    expect(suggestSocialProfileUrl('github', 'github', 'https://github.com/ada')).toBe(
      'https://github.com/ada'
    );
  });
});
