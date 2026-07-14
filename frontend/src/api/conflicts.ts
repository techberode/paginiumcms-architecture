// frontend/src/api/conflicts.ts
// === Conflicts API (Iterácia 3, admin) ===
// Typované volania admin prehľadu konfliktov /api/admin/conflicts.
import apiClient from './client';

export interface ConflictRecord {
  resourceId: string;
  userId: string;
  userName: string;
  baseRevision: string;
  serverRevision: string;
  occurredAt: number;
}

/**
 * Načíta najnovšie zachytené konflikty (admin).
 */
export async function listConflicts(limit = 100): Promise<ConflictRecord[]> {
  const res = await apiClient.get<{ conflicts: ConflictRecord[] }>('/api/admin/conflicts', {
    params: { limit },
  });
  return res.success && res.data ? res.data.conflicts : [];
}

/**
 * Vyčistí log konfliktov (admin).
 */
export async function clearConflicts(): Promise<boolean> {
  const res = await apiClient.delete('/api/admin/conflicts');
  return res.success;
}
