import { describe, expect, it } from 'vitest';
import { isSafeNavigationUrl, safeOptionalHref, sanitizeNavigationUrl } from './safeUrl';

describe('safeUrl security', () => {
  it('allows relative and in-page links', () => {
    expect(isSafeNavigationUrl('/blog')).toBe(true);
    expect(isSafeNavigationUrl('#section')).toBe(true);
    expect(isSafeNavigationUrl('')).toBe(true);
  });

  it('allows http and https links', () => {
    expect(isSafeNavigationUrl('https://example.com')).toBe(true);
    expect(isSafeNavigationUrl('http://localhost:8080/admin')).toBe(true);
  });

  it('blocks dangerous protocols', () => {
    expect(isSafeNavigationUrl('javascript:alert(1)')).toBe(false);
    expect(isSafeNavigationUrl('data:text/html,<script>alert(1)</script>')).toBe(false);
    expect(isSafeNavigationUrl('vbscript:msgbox("x")')).toBe(false);
  });

  it('blocks scheme obfuscation and protocol-relative hosts', () => {
    expect(isSafeNavigationUrl('javascript://example.com/%0aalert(1)')).toBe(false);
    expect(isSafeNavigationUrl('java\tscript:alert(1)')).toBe(false);
    expect(isSafeNavigationUrl(' JAVASCRIPT:alert(1)')).toBe(false);
    expect(isSafeNavigationUrl('//evil.example')).toBe(false);
    expect(isSafeNavigationUrl('/\\evil.example')).toBe(false);
    expect(isSafeNavigationUrl('ftp://example.com')).toBe(false);
  });

  it('safeOptionalHref drops unsafe and empty values', () => {
    expect(safeOptionalHref('javascript://x/%0aalert(1)')).toBeUndefined();
    expect(safeOptionalHref('  ')).toBeUndefined();
    expect(safeOptionalHref(null)).toBeUndefined();
    expect(safeOptionalHref(' https://ok.test ')).toBe('https://ok.test');
  });

  it('sanitizes unsafe urls to fallback', () => {
    expect(sanitizeNavigationUrl('javascript:alert(1)')).toBe('/');
    expect(sanitizeNavigationUrl('https://ok.test', '/')).toBe('https://ok.test');
  });
});
