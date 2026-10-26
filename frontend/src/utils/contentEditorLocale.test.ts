import { describe, expect, it } from 'vitest';
import {
  hydrateLocaleEditorFromLoad,
  resolveEditorLocaleAfterLoad,
  resolveInitialEditorLocale,
} from './contentEditorLocale';
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

  it('uses flat ogImage in editor when locale slice seo ogImage is empty', () => {
    const data: ContentEditorLoadData = {
      title: 'Post',
      content: 'Body',
      status: 'published',
      ogImage: '/storage/app/content/media/hero.jpg',
      defaultLocale: 'sk',
      schemaVersion: 2,
      localizedContent: {
        sk: {
          title: 'Post',
          body: 'Body',
          seo: { title: '', description: '', canonical: '', ogImage: '', noIndex: false },
        },
      },
      localeStatus: { sk: 'published' },
    };

    const hydrated = hydrateLocaleEditorFromLoad(data, 'markdown', 'markdown');

    expect(hydrated.localeStates.sk?.seo.ogImage).toBe('/storage/app/content/media/hero.jpg');
  });

  it('falls back to non-default locale slice when default locale is empty', () => {
    const data: ContentEditorLoadData = {
      title: '',
      content: '',
      status: 'draft',
      defaultLocale: 'sk',
      schemaVersion: 2,
      localizedContent: {
        sk: { title: '', body: '', seo: { title: '' } },
        en: { title: 'EN only', body: 'EN body', seo: { title: 'EN SEO' } },
      },
      localeStatus: { sk: 'draft', en: 'published' },
    };

    const hydrated = hydrateLocaleEditorFromLoad(data, 'markdown', 'markdown');

    expect(hydrated.localeStates.sk?.title).toBe('EN only');
    expect(hydrated.localeStates.en?.title).toBe('EN only');
  });

  it('resolveInitialEditorLocale prefers locale with content over empty admin UI locale', () => {
    const data: ContentEditorLoadData = {
      title: 'SK',
      content: 'SK body',
      status: 'published',
      defaultLocale: 'sk',
      schemaVersion: 2,
      localizedContent: {
        sk: { title: 'SK', body: 'SK body', seo: { title: '' } },
        en: { title: '', body: '', seo: { title: '' } },
      },
      localeStatus: { sk: 'published', en: 'draft' },
    };

    const hydrated = hydrateLocaleEditorFromLoad(data, 'markdown', 'markdown');
    expect(resolveInitialEditorLocale('sk', 'en', hydrated.localeStates)).toBe('sk');
  });

  it('resolveEditorLocaleAfterLoad switches to applied locale when present', () => {
    const data: ContentEditorLoadData = {
      title: 'SK',
      content: 'SK body',
      status: 'published',
      defaultLocale: 'sk',
      schemaVersion: 2,
      localizedContent: {
        sk: { title: 'SK', body: 'SK body', seo: { title: '' } },
        en: { title: 'EN', body: 'EN body', seo: { title: '' } },
      },
      localeStatus: { sk: 'published', en: 'draft' },
    };

    const hydrated = hydrateLocaleEditorFromLoad(data, 'markdown', 'markdown');
    expect(resolveEditorLocaleAfterLoad('en', 'sk', 'sk', hydrated.localeStates)).toBe('en');
    expect(resolveEditorLocaleAfterLoad('xx', 'sk', 'sk', hydrated.localeStates)).toBe('sk');
  });
});
