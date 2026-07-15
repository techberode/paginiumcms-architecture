// frontend/src/api/media.ts
// === Media API (Iteration 8) ===
// Typed calls to backend /api/media/*. Backend is the single source of truth.
import apiClient from './client';
import { resolveMediaUrl as resolveMediaUrlFromBase } from '../utils/apiBaseUrl';

export interface MediaFile {
  id: string;
  path: string;
  fileName: string;
  url: string;
  sizeBytes: number;
  mimeType: string;
  uploadedAt: number;
  altText: string;
}

export interface ListMediaFilters {
  type?: 'image';
  mimeType?: string;
}

export type UploadMediaResult =
  | { ok: true; media: MediaFile }
  | { ok: false; error: string };

/** Resolve a backend-relative media URL to an absolute URL for <img src>. */
export function resolveMediaUrl(url: string): string {
  return resolveMediaUrlFromBase(url);
}

/** Human-readable file size. */
export function formatMediaSize(bytes: number): string {
  if (bytes < 1024) {
    return `${bytes} B`;
  }
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`;
  }
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function isImageMedia(file: MediaFile): boolean {
  return file.mimeType.startsWith('image/');
}

/**
 * List media files with optional filters (`type=image`, `mimeType=...`).
 */
export async function listMedia(filters: ListMediaFilters = {}): Promise<MediaFile[]> {
  const params = new URLSearchParams();
  if (filters.type) {
    params.set('type', filters.type);
  }
  if (filters.mimeType) {
    params.set('mimeType', filters.mimeType);
  }

  const query = params.toString();
  const url = query ? `/api/media?${query}` : '/api/media';
  const res = await apiClient.get<MediaFile[]>(url);

  return res.success && Array.isArray(res.data) ? res.data : [];
}

/**
 * Upload a file via multipart/form-data (`file`, optional `altText`).
 */
export async function uploadMedia(file: File, altText = ''): Promise<UploadMediaResult> {
  const form = new FormData();
  form.append('file', file);
  form.append('altText', altText);

  const res = await apiClient.post<MediaFile>('/api/media/upload', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });

  if (res.success && res.data) {
    return { ok: true, media: res.data };
  }

  return { ok: false, error: res.error ?? 'Upload failed.' };
}

/**
 * Update media metadata (currently alt text only).
 */
export async function updateMediaAlt(path: string, altText: string): Promise<boolean> {
  const res = await apiClient.patch<MediaFile>(
    `/api/media/${encodeURIComponent(path)}`,
    { altText }
  );

  return res.success;
}

/** Delete a media file by its storage path. */
export async function deleteMedia(path: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/media/${encodeURIComponent(path)}`);
  return res.success;
}
