import type { SeoFormValues } from '../components/backend/SeoMetadataPanel';
import type { ContentFormat, EditorMode } from './contentEditor';
import { valueForEditorMode } from './contentEditor';
import type { ContentEditorLoadData, LocalizedContentSlice } from './contentEditorApi';
import type { ContentEditorStatus } from './contentScheduling';
import { isoToDatetimeLocalValue } from './contentScheduling';
import { SUPPORTED_LOCALES } from '../i18n/types';
import { bodyLooksLikeMetadataLeak, stripEmbeddedMetadataLeak } from './contentBodySanitizer';

export type ContentLocaleCode = (typeof SUPPORTED_LOCALES)[number];

export interface LocaleEditorState {
  title: string;
  content: string;
  contentFormat: ContentFormat;
  status: ContentEditorStatus;
  scheduledAt: string;
  seo: SeoFormValues;
}

export function emptyLocaleEditorState(): LocaleEditorState {
  return {
    title: '',
    content: '',
    contentFormat: 'markdown',
    status: 'draft',
    scheduledAt: '',
    seo: {
      seoTitle: '',
      seoDescription: '',
      canonical: '',
      ogImage: '',
      noIndex: false,
      tags: '',
    },
  };
}

export function sliceFromLocaleState(state: LocaleEditorState, storedContent: string): LocalizedContentSlice {
  return {
    title: state.title,
    body: storedContent,
    seo: {
      title: state.seo.seoTitle,
      description: state.seo.seoDescription,
      canonical: state.seo.canonical,
      ogImage: state.seo.ogImage,
      noIndex: state.seo.noIndex,
    },
  };
}

export function localeStateFromSlice(
  slice: LocalizedContentSlice | undefined,
  localeStatus: ContentEditorStatus,
  editorMode: EditorMode,
  fallbackFormat: ContentFormat = 'markdown',
  fallback?: { title?: string; body?: string }
): LocaleEditorState {
  if (!slice) {
    return { ...emptyLocaleEditorState(), status: localeStatus };
  }

  let rawBody = String(slice.body ?? '');
  const fallbackBody = String(fallback?.body ?? '');
  if (bodyLooksLikeMetadataLeak(rawBody)) {
    rawBody = stripEmbeddedMetadataLeak(rawBody);
  }
  if (rawBody.trim() === '' && fallbackBody.trim() !== '' && !bodyLooksLikeMetadataLeak(fallbackBody)) {
    rawBody = fallbackBody;
  }

  const fallbackTitle = String(fallback?.title ?? '').trim();
  const title = String(slice.title ?? '').trim() !== '' ? String(slice.title ?? '') : fallbackTitle;
  const format = fallbackFormat;

  return {
    title,
    content: valueForEditorMode(rawBody, format, editorMode),
    contentFormat: format,
    status: localeStatus,
    scheduledAt: '',
    seo: {
      seoTitle: String(slice.seo?.title ?? ''),
      seoDescription: String(slice.seo?.description ?? ''),
      canonical: String(slice.seo?.canonical ?? ''),
      ogImage: String(slice.seo?.ogImage ?? ''),
      noIndex: Boolean(slice.seo?.noIndex ?? false),
      tags: '',
    },
  };
}

export interface HydratedLocaleEditor {
  defaultLocale: ContentLocaleCode;
  localeStates: Partial<Record<ContentLocaleCode, LocaleEditorState>>;
  localeStatus: Partial<Record<ContentLocaleCode, ContentEditorStatus>>;
}

export function hydrateLocaleEditorFromLoad(
  data: ContentEditorLoadData,
  editorMode: EditorMode,
  contentFormat: ContentFormat
): HydratedLocaleEditor {
  const defaultLocale = normalizeContentLocale(data.defaultLocale) ?? 'sk';
  const localized = data.localizedContent ?? {};
  const statusMap = data.localeStatus ?? {};
  const localeStates: Partial<Record<ContentLocaleCode, LocaleEditorState>> = {};
  const flatFallback = {
    title: data.title ?? '',
    body: bodyLooksLikeMetadataLeak(String(data.content ?? ''))
      ? stripEmbeddedMetadataLeak(String(data.content ?? ''))
      : String(data.content ?? ''),
  };

  for (const code of SUPPORTED_LOCALES) {
    const slice = localized[code];
    const localeStatus = (statusMap[code] as ContentEditorStatus | undefined) ?? 'draft';
    if (slice || code === defaultLocale) {
      localeStates[code] = localeStateFromSlice(
        slice,
        localeStatus,
        editorMode,
        contentFormat,
        code === defaultLocale ? flatFallback : undefined
      );
    }
  }

  const fallbackSlice = Object.values(localized).find(
    (slice) => slice && (String(slice.title ?? '').trim() !== '' || String(slice.body ?? '').trim() !== '')
  );
  const defaultState = localeStates[defaultLocale];
  if (
    defaultState
    && defaultState.title.trim() === ''
    && defaultState.content.trim() === ''
    && fallbackSlice
  ) {
    localeStates[defaultLocale] = localeStateFromSlice(
      fallbackSlice,
      (statusMap[defaultLocale] as ContentEditorStatus | undefined)
        ?? (data.status as ContentEditorStatus | undefined)
        ?? 'draft',
      editorMode,
      contentFormat
    );
  }

  if (!localeStates[defaultLocale]) {
    localeStates[defaultLocale] = localeStateFromSlice(
      {
        title: data.title ?? '',
        body: data.content ?? '',
        seo: {
          title: data.seoTitle,
          description: data.seoDescription,
          canonical: data.canonical,
          ogImage: data.ogImage ?? data.featuredImage,
          noIndex: data.noIndex,
        },
      },
      (data.status as ContentEditorStatus) ?? 'draft',
      editorMode,
      contentFormat
    );
    localeStates[defaultLocale]!.scheduledAt = isoToDatetimeLocalValue(String(data.scheduledAt ?? ''));
    localeStates[defaultLocale]!.seo.tags = Array.isArray(data.tags) ? data.tags.join(', ') : '';
  }

  const localeStatus: Partial<Record<ContentLocaleCode, ContentEditorStatus>> = {};
  for (const code of SUPPORTED_LOCALES) {
    if (statusMap[code]) {
      localeStatus[code] = statusMap[code] as ContentEditorStatus;
    } else if (localeStates[code]) {
      localeStatus[code] = localeStates[code]!.status;
    }
  }

  if (localeStates[defaultLocale]) {
    localeStates[defaultLocale]!.scheduledAt = isoToDatetimeLocalValue(String(data.scheduledAt ?? ''));
    localeStates[defaultLocale]!.seo.tags = Array.isArray(data.tags) ? data.tags.join(', ') : '';
  }

  return { defaultLocale, localeStates, localeStatus };
}

export function captureLocaleEditorState(input: {
  title: string;
  content: string;
  contentFormat: ContentFormat;
  status: ContentEditorStatus;
  scheduledAt: string;
  seo: SeoFormValues;
}): LocaleEditorState {
  return {
    title: input.title,
    content: input.content,
    contentFormat: input.contentFormat,
    status: input.status,
    scheduledAt: input.scheduledAt,
    seo: { ...input.seo },
  };
}

export function applyLocaleEditorState(
  state: LocaleEditorState,
  editorMode: EditorMode
): {
  title: string;
  content: string;
  contentFormat: ContentFormat;
  status: ContentEditorStatus;
  scheduledAt: string;
  seo: SeoFormValues;
} {
  return {
    title: state.title,
    content: valueForEditorMode(state.content, state.contentFormat, editorMode),
    contentFormat: state.contentFormat,
    status: state.status,
    scheduledAt: state.scheduledAt,
    seo: { ...state.seo },
  };
}

export function normalizeContentLocale(value: string | undefined): ContentLocaleCode | null {
  const code = (value ?? '').trim().toLowerCase();
  return SUPPORTED_LOCALES.includes(code as ContentLocaleCode) ? (code as ContentLocaleCode) : null;
}

export function resolveInitialEditorLocale(
  defaultLocale: ContentLocaleCode,
  adminUiLocale: string,
  localeStates: Partial<Record<ContentLocaleCode, LocaleEditorState>> = {}
): ContentLocaleCode {
  const preferred = normalizeContentLocale(adminUiLocale) ?? defaultLocale;
  const preferredState = localeStates[preferred];
  if (preferredState && (preferredState.title.trim() !== '' || preferredState.content.trim() !== '')) {
    return preferred;
  }

  for (const code of SUPPORTED_LOCALES) {
    const state = localeStates[code];
    if (state && (state.title.trim() !== '' || state.content.trim() !== '')) {
      return code;
    }
  }

  return defaultLocale;
}
