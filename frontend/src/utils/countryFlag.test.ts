import { describe, expect, it } from 'vitest';
import { countryCodeToFlag, refererTypeIcon } from './countryFlag';

describe('countryCodeToFlag', () => {
  it('returns SK flag for valid code', () => {
    expect(countryCodeToFlag('SK')).toBe('🇸🇰');
  });

  it('returns globe for invalid code', () => {
    expect(countryCodeToFlag(null)).toBe('🌍');
    expect(countryCodeToFlag('SLO')).toBe('🌍');
  });
});

describe('refererTypeIcon', () => {
  it('maps known referer types', () => {
    expect(refererTypeIcon('direct')).toBe('↩');
    expect(refererTypeIcon('search')).toBe('🔍');
    expect(refererTypeIcon('social')).toBe('💬');
    expect(refererTypeIcon('referral')).toBe('🔗');
  });
});
