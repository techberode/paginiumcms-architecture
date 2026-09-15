export interface AdminStorageMeterInput {
  free_space_bytes?: number | null;
  demo_synthetic?: boolean;
  demo_quota_bytes?: number;
  demo_used_bytes?: number;
  content?: {
    total_bytes: number;
  };
}

/** Used% for the dashboard storage bar. Null when we cannot compute a ratio. */
export function storageUsedPercent(storage: AdminStorageMeterInput | null | undefined): number | null {
  if (!storage) {
    return null;
  }

  if (
    storage.demo_synthetic === true &&
    typeof storage.demo_quota_bytes === 'number' &&
    storage.demo_quota_bytes > 0 &&
    typeof storage.demo_used_bytes === 'number'
  ) {
    return Math.max(0, Math.min(100, Math.round((100 * storage.demo_used_bytes) / storage.demo_quota_bytes)));
  }

  const used = storage.content?.total_bytes;
  const free = storage.free_space_bytes;
  if (typeof used === 'number' && typeof free === 'number' && used + free > 0) {
    return Math.max(0, Math.min(100, Math.round((100 * used) / (used + free))));
  }

  return null;
}
