import { describe, expect, it, beforeEach } from 'vitest';
import {
  addTrustedImageSender,
  isTrustedImageSender,
  parseSenderEmail,
  readTrustedImageSenders,
} from './mailTrustedImageSenders';

describe('mailTrustedImageSenders', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  it('parses angle-addr and plain addresses', () => {
    expect(parseSenderEmail('Acme <team@example.com>')).toBe('team@example.com');
    expect(parseSenderEmail('team@example.com')).toBe('team@example.com');
  });

  it('defaults to blocked until sender is trusted', () => {
    expect(isTrustedImageSender('editor@site.test', 'News <news@partner.com>')).toBe(false);
    addTrustedImageSender('editor@site.test', 'News <news@partner.com>');
    expect(readTrustedImageSenders('editor@site.test')).toEqual(['news@partner.com']);
    expect(isTrustedImageSender('editor@site.test', 'news@partner.com')).toBe(true);
  });
});
