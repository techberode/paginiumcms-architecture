// frontend/src/api/drafts.ts
// === Drafts API (Iterácia 2) ===
// Typované volania auto-save konceptov /api/drafts/{type}/{slug}.
import apiClient from './client';

export type ContentType = 'page' | 'article';

export interface Draft {
  type: ContentType;
  slug: string;
  title: string;
  content: string;
  status: string;
  baseRevision: string;
  savedBy: string;
  savedAt: number;
}

export interface DraftPayload {
  title: string;
  content: string;
  status: string;
  baseRevision: string;
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
