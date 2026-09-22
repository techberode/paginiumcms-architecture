import { describe, expect, it } from 'vitest';
import { isDefaultSiteName, isSiteBrandingConfigured } from './gettingStartedStatus';
import type { PublicSettings } from '../api/settings';

const baseSettings: PublicSettings = {
  general: { siteName: 'PaginiumCMS', language: 'sk' },
  content: {},
  editor: {},
};

describe('gettingStartedStatus', () => {
  it('treats default site name as incomplete', () => {
    expect(isDefaultSiteName('PaginiumCMS')).toBe(true);
    expect(isDefaultSiteName('  paginium cms  ')).toBe(true);
    expect(isSiteBrandingConfigured(baseSettings)).toBe(false);
  });

  it('accepts custom site name', () => {
    expect(
      isSiteBrandingConfigured({
        ...baseSettings,
        general: { ...baseSettings.general, siteName: 'Môj web' },
      })
    ).toBe(true);
  });

  it('accepts description when site name is still default', () => {
    expect(
      isSiteBrandingConfigured({
        ...baseSettings,
        general: { ...baseSettings.general, siteDescription: 'Popis webu' },
      })
    ).toBe(true);
  });
});
