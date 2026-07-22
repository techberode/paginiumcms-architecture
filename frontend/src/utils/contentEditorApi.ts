import type { ContentFormat, EditorMode } from './contentEditor';
import type { EditorProfileId } from './editorProfiles';

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
  status?: 'draft' | 'published' | 'archived';
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
}

export interface ContentSaveResponse {
  slug?: string;
  revision?: string;
  path?: string;
}
