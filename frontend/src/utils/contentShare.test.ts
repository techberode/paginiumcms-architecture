import { describe, expect, it } from 'vitest';
import {
  contentShareHref,
  resolveContentShareSettings,
  shouldShowContentShare,
  visibleShareNetworks,
} from './contentShare';

describe('contentShare', () => {
  it('defaults articles on and pages off', () => {
    const settings = resolveContentShareSettings(undefined);
    expect(settings.enabled).toBe(true);
    expect(settings.onArticles).toBe(true);
    expect(settings.onPages).toBe(false);
    expect(visibleShareNetworks(settings)).toEqual(['facebook', 'x', 'linkedin', 'email', 'copy']);
  });

  it('hides networks when master switch is off', () => {
    expect(visibleShareNetworks(resolveContentShareSettings({ shareEnabled: false }))).toEqual([]);
  });

  it('builds intent URLs without third-party SDKs', () => {
    expect(contentShareHref('facebook', 'https://example.com/a', 'Hello')).toContain('facebook.com/sharer');
    expect(contentShareHref('x', 'https://example.com/a', 'Hello')).toContain('twitter.com/intent/tweet');
  });

  it('never shares the home page even when pages are enabled', () => {
    const settings = resolveContentShareSettings({ shareOnPages: true });
    expect(shouldShowContentShare(settings, 'page', { isHome: true })).toBe(false);
    expect(shouldShowContentShare(settings, 'page')).toBe(true);
  });
});
