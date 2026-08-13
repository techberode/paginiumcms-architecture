import type { ContentFormat, EditorMode } from './contentEditor';
import type { EditorProfileId } from './editorProfiles';
import type { ContentEditorStatus } from './contentScheduling';

export interface LocalizedContentSlice {
  title?: string;
  body?: string;
  seo?: {
    title?: string;
    description?: string;
    canonical?: string;
    ogImage?: string;
    noIndex?: boolean;
  };
}

/** Payload returned by GET /api/pages/{slug} and /api/articles/{slug} in the admin editor. */
export interface ContentEditorLoadData {
  title?: string;
  slug?: string;
  content?: string;
  frontMatter?: Record<string, unknown>;
  contentFormat?: ContentFormat;
  editorMode?: EditorMode;
  editorProfile?: EditorProfileId;
  revision?: string;
  path?: string;
  template?: string;
  layoutTemplate?: string;
  status?: ContentEditorStatus;
  scheduledAt?: string;
  createdAt?: string;
  updatedAt?: string;
  seoTitle?: string;
  seoDescription?: string;
  canonical?: string;
  ogImage?: string;
  featuredImage?: string;
  noIndex?: boolean;
  tags?: string[];
  commentsEnabled?: boolean;
  commentsRequireApproval?: boolean | null;
  commentsAllowGuests?: boolean | null;
  author?: string;
  schemaVersion?: number;
  defaultLocale?: string;
  localizedContent?: Record<string, LocalizedContentSlice>;
  localeStatus?: Record<string, ContentEditorStatus>;
}

export interface ContentSaveResponse {
  slug?: string;
  revision?: string;
  path?: string;
  localeStatus?: Record<string, ContentEditorStatus>;
}
