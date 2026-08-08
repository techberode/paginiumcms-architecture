import { describe, expect, it } from 'vitest';
import { hydrateLocaleEditorFromLoad } from './contentEditorLocale';
import type { ContentEditorLoadData } from './contentEditorApi';

describe('contentEditorLocale', () => {
  it('hydrates multi-locale load data', () => {
    const data: ContentEditorLoadData = {
      title: 'SK',
      content: 'SK body',
      status: 'published',
      defaultLocale: 'sk',
      schemaVersion: 2,
      localizedContent: {
        sk: { title: 'SK', body: 'SK body', seo: { title: 'SK SEO' } },
        en: { title: 'EN', body: 'EN body', seo: { title: 'EN SEO' } },
      },
      localeStatus: { sk: 'published', en: 'draft' },
    };

    const hydrated = hydrateLocaleEditorFromLoad(data, 'markdown', 'markdown');

    expect(hydrated.defaultLocale).toBe('sk');
    expect(hydrated.localeStates.sk?.title).toBe('SK');
    expect(hydrated.localeStates.en?.title).toBe('EN');
    expect(hydrated.localeStatus.en).toBe('draft');
  });
});
