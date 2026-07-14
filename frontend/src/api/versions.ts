// frontend/src/api/versions.ts
// === Versions API (Iterácia 2) ===
// Typované volania na verzovacie endpointy /api/admin/versions/*.
import apiClient from './client';

/** Jeden riadok diffu (formát z backend DiffGenerator). */
export interface DiffLine {
  type: 'unchanged' | 'added' | 'removed' | 'modified';
  old_line: number | null;
  new_line: number | null;
  content?: string;
  old_content?: string;
  new_content?: string;
}

export interface DiffResult {
  content: string;
  front_matter: string;
  additions: number;
  deletions: number;
  modifications: number;
  lines: DiffLine[];
}

export interface VersionComparison {
  version1: { number: number; timestamp: string; author: string };
  version2: { number: number; timestamp: string; author: string };
  diff: DiffResult;
  summary: string;
}

export interface VersionHistoryItem {
  version: number;
  created_at: string;
  created_by: string;
  message: string;
  diff_summary: string;
  size: number;
}

/**
 * Načíta históriu verzií pre daný obsah.
 */
export async function getVersionHistory(contentId: string): Promise<VersionHistoryItem[]> {
  const res = await apiClient.get<{ versions: VersionHistoryItem[] }>(
    `/api/admin/versions/${encodeURIComponent(contentId)}`
  );
  return res.success && res.data ? res.data.versions : [];
}

/**
 * Porovná dve verzie a vráti podrobný diff pre DiffViewer.
 */
export async function compareVersions(
  contentId: string,
  version1: number,
  version2: number
): Promise<VersionComparison | null> {
  const res = await apiClient.get<VersionComparison>('/api/admin/versions/compare', {
    params: { content_id: contentId, version1, version2 },
  });
  return res.success && res.data ? res.data : null;
}

/**
 * Obnoví konkrétnu verziu do live obsahu.
 */
export async function restoreVersion(contentId: string, version: number): Promise<boolean> {
  const res = await apiClient.post('/api/admin/versions/restore', {
    content_id: contentId,
    version,
  });
  return res.success;
}
