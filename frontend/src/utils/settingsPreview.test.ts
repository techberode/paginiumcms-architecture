import { describe, expect, it } from 'vitest';
import {
  isPublicSettingsGroup,
  mergePublicSettings,
  omitPreviewGroup,
  previewPatchFromGroup,
} from './settingsPreview';
import type { PublicSettings } from '../api/settings';

const base: PublicSettings = {
  general: { siteName: 'Paginium', language: 'sk' },
  content: {},
  editor: {},
  ui: {
    sidebarColor: 'default',
    navPlacement: 'side',
    chromeGradient: false,
  },
};

describe('settingsPreview', () => {
  it('merges nested public groups without dropping sibling keys', () => {
    const merged = mergePublicSettings(base, {
      ui: { sidebarColor: 'navy', chromeGradient: true },
    });

    expect(merged.ui?.sidebarColor).toBe('navy');
    expect(merged.ui?.chromeGradient).toBe(true);
    expect(merged.ui?.navPlacement).toBe('side');
    expect(merged.general.siteName).toBe('Paginium');
  });

  it('builds a preview patch only for public groups', () => {
    expect(isPublicSettingsGroup('ui')).toBe(true);
    expect(isPublicSettingsGroup('logging')).toBe(false);
    expect(previewPatchFromGroup('logging', { retentionDays: 7 })).toBeNull();
    expect(previewPatchFromGroup('ui', { sidebarColor: 'navy' })).toEqual({
      ui: { sidebarColor: 'navy' },
    });
  });

  it('drops one previewed group and clears an empty patch', () => {
    const patch = {
      ui: { sidebarColor: 'navy' },
      appearance: { colorScheme: 'indigo-classic', mode: 'system' as const, allowUserToggle: true },
    };
    expect(omitPreviewGroup(patch, 'ui')).toEqual({
      appearance: { colorScheme: 'indigo-classic', mode: 'system', allowUserToggle: true },
    });
    expect(omitPreviewGroup({ ui: { sidebarColor: 'navy' } }, 'ui')).toBeNull();
  });
});
