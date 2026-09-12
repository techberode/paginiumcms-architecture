// frontend/src/api/drafts.ts
// === Drafts API (Iterácia 2) ===
// Typované volania auto-save konceptov /api/drafts/{type}/{slug}.
import type { ArticleAuthorSettings } from '../utils/articleAuthorSettings';
import type { ArticleCommentsSettings } from '../utils/articleCommentsSettings';
import type { EditorMode } from '../utils/contentEditor';
import type { ContentLocaleCode, LocaleEditorState } from '../utils/contentEditorLocale';
import type { ContentEditorStatus } from '../utils/contentScheduling';
import type { EditorProfileId } from '../utils/editorProfiles';
import apiClient from './client';

export type ContentType = 'page' | 'article';

/** Voliteľný rozšírený stav editora (Nastavenia → Obsah → draftFullEditorState). */
export interface DraftEditorSnapshot {
  activeLocale: ContentLocaleCode;
  localeStates: Partial<Record<ContentLocaleCode, LocaleEditorState>>;
  localeStatusMap?: Partial<Record<ContentLocaleCode, ContentEditorStatus>>;
  editorMode?: EditorMode;
  editorProfile?: EditorProfileId;
  template?: string;
  layoutTemplate?: string;
  editSlug?: string;
  scheduledAt?: string;
  articleCategory?: string;
  articleComments?: ArticleCommentsSettings;
  articleAuthorSettings?: ArticleAuthorSettings;
}

export interface Draft {
  type: ContentType;
  slug: string;
  title: string;
  content: string;
  status: string;
  baseRevision: string;
  savedBy: string;
  savedAt: number;
  editorSnapshot?: DraftEditorSnapshot;
}

export interface DraftPayload {
  title: string;
  content: string;
  status: string;
  baseRevision: string;
  editorSnapshot?: DraftEditorSnapshot;
}

/**
 * Uloží koncept (auto-save). Vracia true pri úspechu.
 */
export async function saveDraft(type: ContentType, slug: string, payload: DraftPayload): Promise<boolean> {
  const res = await apiClient.put(`/api/drafts/${type}/${encodeURIComponent(slug)}`, payload);
  return res.success;
}

/**
 * Načíta uložený koncept (obnova rozpracovaného obsahu). null ak neexistuje.
 */
export async function loadDraft(type: ContentType, slug: string): Promise<Draft | null> {
  const res = await apiClient.get<Draft>(`/api/drafts/${type}/${encodeURIComponent(slug)}`);
  return res.success && res.data ? res.data : null;
}

/**
 * Zahodí koncept (napr. po úspešnom uložení publikovaného obsahu).
 */
export async function discardDraft(type: ContentType, slug: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/drafts/${type}/${encodeURIComponent(slug)}`);
  return res.success;
}
