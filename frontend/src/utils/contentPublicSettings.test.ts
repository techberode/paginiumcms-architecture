import { describe, expect, it } from 'vitest';
import { resolveArticlePrintEnabled, resolveBoolSetting } from './contentPublicSettings';

describe('contentPublicSettings', () => {
  it('resolveBoolSetting accepts common truthy/falsy shapes', () => {
    expect(resolveBoolSetting(true)).toBe(true);
    expect(resolveBoolSetting('true')).toBe(true);
    expect(resolveBoolSetting(1)).toBe(true);
    expect(resolveBoolSetting(false)).toBe(false);
    expect(resolveBoolSetting(undefined, true)).toBe(true);
  });

  it('resolveArticlePrintEnabled defaults to false', () => {
    expect(resolveArticlePrintEnabled(undefined)).toBe(false);
    expect(resolveArticlePrintEnabled({})).toBe(false);
    expect(resolveArticlePrintEnabled({ articlePrintEnabled: true })).toBe(true);
  });
});
