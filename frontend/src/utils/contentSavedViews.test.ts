import { describe, expect, it } from 'vitest';
import {
  addCustomContentView,
  contentFilterPresetsEqual,
  contentSavedViewsStorageKey,
  hideDefaultContentView,
  mergeVisibleContentViews,
  normalizeContentFilterPreset,
  parseContentSavedViewsStorage,
  serializeContentSavedViewsStorage,
} from './contentSavedViews';

describe('contentSavedViews', () => {
  it('builds stable storage keys per user and content type', () => {
    expect(contentSavedViewsStorageKey('user-1', 'pages')).toBe('paginium:content-views:user-1:pages');
    expect(contentSavedViewsStorageKey('', 'articles')).toBe('paginium:content-views:anonymous:articles');
  });

  it('serializes and restores custom views', () => {
    const state = {
      hiddenDefaultIds: ['default:published'],
      customViews: [
        {
          id: 'custom:1',
          name: 'My drafts',
          preset: normalizeContentFilterPreset({
            status: 'draft',
            search: 'beta',
            seoIssuesOnly: true,
            sortField: 'title',
            sortDirection: 'asc',
          }),
        },
      ],
    };

    const restored = parseContentSavedViewsStorage(serializeContentSavedViewsStorage(state));
    expect(restored.hiddenDefaultIds).toEqual(['default:published']);
    expect(restored.customViews).toHaveLength(1);
    expect(restored.customViews[0]?.name).toBe('My drafts');
    expect(restored.customViews[0]?.preset.search).toBe('beta');
  });

  it('merges visible defaults and customs while hiding defaults', () => {
    const views = mergeVisibleContentViews({
      hiddenDefaultIds: ['default:published'],
      customViews: [
        {
          id: 'custom:1',
          name: 'Review queue',
          preset: normalizeContentFilterPreset({ status: 'draft', search: 'review' }),
        },
      ],
    });

    expect(views.some((view) => view.id === 'default:published')).toBe(false);
    expect(views.some((view) => view.id === 'default:drafts')).toBe(true);
    expect(views.some((view) => view.id === 'custom:1')).toBe(true);
  });

  it('compares filter presets exactly', () => {
    const a = normalizeContentFilterPreset({ status: 'draft', search: 'x', sortDirection: 'asc' });
    const b = normalizeContentFilterPreset({ status: 'draft', search: 'x', sortDirection: 'asc' });
    const c = normalizeContentFilterPreset({ status: 'published', search: 'x', sortDirection: 'asc' });

    expect(contentFilterPresetsEqual(a, b)).toBe(true);
    expect(contentFilterPresetsEqual(a, c)).toBe(false);
  });

  it('enforces custom view limit and hides default views', () => {
    let state = parseContentSavedViewsStorage(null);

    for (let index = 0; index < 5; index += 1) {
      const result = addCustomContentView(state, `View ${index}`, normalizeContentFilterPreset({ status: 'draft' }));
      expect(result.view).not.toBeNull();
      state = result.state;
    }

    const limited = addCustomContentView(state, 'Too many', normalizeContentFilterPreset({ status: 'draft' }));
    expect(limited.error).toBe('limit');

    state = hideDefaultContentView(state, 'default:scheduled');
    expect(state.hiddenDefaultIds).toContain('default:scheduled');
  });
});
