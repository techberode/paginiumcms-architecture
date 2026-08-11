import { describe, expect, it } from 'vitest';
import { BUILTIN_COOKIE_POLICY_PATH, resolveCookiePolicyHref } from './cookiePolicyUrl';

describe('resolveCookiePolicyHref', () => {
  it('defaults empty url to built-in cookies page', () => {
    expect(resolveCookiePolicyHref('')).toEqual({
      href: BUILTIN_COOKIE_POLICY_PATH,
      external: false,
    });
  });

  it('treats relative paths as internal', () => {
    expect(resolveCookiePolicyHref('/legal/privacy')).toEqual({
      href: '/legal/privacy',
      external: false,
    });
  });

  it('treats absolute urls as external', () => {
    expect(resolveCookiePolicyHref('https://example.com/privacy')).toEqual({
      href: 'https://example.com/privacy',
      external: true,
    });
  });
});
