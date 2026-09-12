import type { DraftEditorSnapshot } from '../api/drafts';
import type { SeoFormValues } from '../components/backend/SeoMetadataPanel';
import type { ContentType } from '../api/drafts';
import type { ArticleAuthorSettings } from './articleAuthorSettings';
import type { ArticleCommentsSettings } from './articleCommentsSettings';
import type { ContentFormat, EditorMode } from './contentEditor';
import {
  applyLocaleEditorState,
  captureLocaleEditorState,
  emptyLocaleEditorState,
  normalizeContentLocale,
  type ContentLocaleCode,
  type LocaleEditorState,
} from './contentEditorLocale';
import type { ContentEditorStatus } from './contentScheduling';
import type { EditorProfileId } from './editorProfiles';

export interface BuildDraftSnapshotInput {
  type: ContentType;
  activeLocale: ContentLocaleCode;
  localeStates: Partial<Record<ContentLocaleCode, LocaleEditorState>>;
  localeStatusMap: Partial<Record<ContentLocaleCode, ContentEditorStatus>>;
  title: string;
  content: string;
  contentFormat: ContentFormat;
  status: ContentEditorStatus;
  scheduledAt: string;
  seo: SeoFormValues;
  editorMode: EditorMode;
  editorProfile: EditorProfileId;
  template: string;
  layoutTemplate: string;
  editSlug: string;
  articleCategory?: string;
  articleComments?: ArticleCommentsSettings;
  articleAuthorSettings?: ArticleAuthorSettings;
}

export function buildDraftEditorSnapshot(input: BuildDraftSnapshotInput): DraftEditorSnapshot {
  const activeState = captureLocaleEditorState({
    title: input.title,
    content: input.content,
    contentFormat: input.contentFormat,
    status: input.status,
    scheduledAt: input.scheduledAt,
    seo: input.seo,
  });

  const snapshot: DraftEditorSnapshot = {
    activeLocale: input.activeLocale,
    localeStates: {
      ...input.localeStates,
      [input.activeLocale]: activeState,
    },
    localeStatusMap: {
      ...input.localeStatusMap,
      [input.activeLocale]: input.status,
    },
    editorMode: input.editorMode,
    editorProfile: input.editorProfile,
    template: input.template,
    layoutTemplate: input.layoutTemplate,
    editSlug: input.editSlug,
    scheduledAt: input.scheduledAt,
  };

  if (input.type === 'article') {
    snapshot.articleCategory = input.articleCategory ?? '';
    snapshot.articleComments = input.articleComments;
    snapshot.articleAuthorSettings = input.articleAuthorSettings;
  }

  return snapshot;
}

export interface AppliedDraftSnapshot {
  activeLocale: ContentLocaleCode;
  localeStates: Partial<Record<ContentLocaleCode, LocaleEditorState>>;
  localeStatusMap: Partial<Record<ContentLocaleCode, ContentEditorStatus>>;
  editorMode: EditorMode;
  editorProfile: EditorProfileId;
  template: string;
  layoutTemplate: string;
  editSlug: string;
  scheduledAt: string;
  articleCategory?: string;
  articleComments?: ArticleCommentsSettings;
  articleAuthorSettings?: ArticleAuthorSettings;
  applied: ReturnType<typeof applyLocaleEditorState>;
}

export function applyDraftEditorSnapshot(
  snapshot: DraftEditorSnapshot,
  fallbackEditorMode: EditorMode
): AppliedDraftSnapshot | null {
  const activeLocale = normalizeContentLocale(snapshot.activeLocale) ?? 'sk';
  const editorMode = snapshot.editorMode === 'wysiwyg' ? 'wysiwyg' : 'markdown';
  const localeStates = snapshot.localeStates ?? {};
  const nextState = localeStates[activeLocale] ?? emptyLocaleEditorState();
  const applied = applyLocaleEditorState(nextState, editorMode);

  return {
    activeLocale,
    localeStates,
    localeStatusMap: snapshot.localeStatusMap ?? {},
    editorMode: snapshot.editorMode === 'wysiwyg' ? 'wysiwyg' : fallbackEditorMode,
    editorProfile: snapshot.editorProfile ?? 'company',
    template: snapshot.template ?? '',
    layoutTemplate: snapshot.layoutTemplate ?? 'hero-content',
    editSlug: snapshot.editSlug ?? '',
    scheduledAt: snapshot.scheduledAt ?? applied.scheduledAt,
    articleCategory: snapshot.articleCategory,
    articleComments: snapshot.articleComments,
    articleAuthorSettings: snapshot.articleAuthorSettings,
    applied,
  };
}
