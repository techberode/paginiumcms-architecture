// frontend/src/api/media.ts
// === Media API (Iteration 8 + 24 DAM) ===
// Typed calls to backend /api/media/*. Backend is the single source of truth.
import apiClient from './client';
import {
  resolveAdminMediaFileUrl,
  resolveMediaUrl as resolveMediaUrlFromBase,
  resolveStorageUrl,
} from '../utils/apiBaseUrl';

export interface MediaFile {
  id: string;
  path: string;
  fileName: string;
  url: string;
  sizeBytes: number;
  mimeType: string;
  uploadedAt: number;
  altText: string;
  folder: string;
  title: string;
}

export interface ListMediaFilters {
  type?: 'image';
  mimeType?: string;
  folder?: string;
}

export type UploadMediaResult =
  | { ok: true; media: MediaFile }
  | { ok: false; error: string };

export interface MediaFormatsPayload {
  mimeTypes: string[];
  extensions: string[];
  accept: string;
  previewableMimeTypes: string[];
}

/** Resolve a backend-relative media URL to an absolute URL for public embeds. */
export function resolveMediaUrl(url: string): string {
  return resolveMediaUrlFromBase(url);
}

/** Same-origin URL for admin thumbnails and lightbox preview. */
export function resolveAdminMediaPreviewUrl(path: string): string {
  return resolveAdminMediaFileUrl(path);
}

/** Public storage URL (same origin). */
export function resolvePublicMediaUrl(url: string): string {
  return resolveStorageUrl(url);
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

export function isPreviewableMedia(
  file: MediaFile,
  previewableMimeTypes?: string[]
): boolean {
  if (previewableMimeTypes && previewableMimeTypes.length > 0) {
    return previewableMimeTypes.includes(file.mimeType);
  }

  return isImageMedia(file) && file.mimeType !== 'application/pdf';
}

/** Load strict allowed formats from backend settings. */
export async function listMediaFormats(): Promise<MediaFormatsPayload> {
  const fallback: MediaFormatsPayload = {
    mimeTypes: [
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/webp',
      'image/svg+xml',
      'application/pdf',
    ],
    extensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'],
    accept: 'image/jpeg,image/png,image/gif,image/webp,image/svg+xml,application/pdf',
    previewableMimeTypes: [
      'image/jpeg',
      'image/png',
      'image/gif',
      'image/webp',
      'image/svg+xml',
    ],
  };

  const res = await apiClient.get<MediaFormatsPayload>('/api/media/formats');
  return res.success && res.data ? res.data : fallback;
}

/**
 * List media files with optional filters (`type=image`, `mimeType=...`, `folder=...`).
 */
export async function listMedia(filters: ListMediaFilters = {}): Promise<MediaFile[]> {
  const params = new URLSearchParams();
  if (filters.type) {
    params.set('type', filters.type);
  }
  if (filters.mimeType) {
    params.set('mimeType', filters.mimeType);
  }
  if (filters.folder !== undefined) {
    params.set('folder', filters.folder);
  }

  const query = params.toString();
  const url = query ? `/api/media?${query}` : '/api/media';
  const res = await apiClient.get<MediaFile[]>(url);

  return res.success && Array.isArray(res.data) ? res.data : [];
}

/** List DAM folder paths (empty string = root). */
export async function listMediaFolders(): Promise<string[]> {
  const res = await apiClient.get<string[]>('/api/media/folders');
  return res.success && Array.isArray(res.data) ? res.data : [''];
}

/** Create a nested folder path (e.g. `campaigns/2026`). */
export async function createMediaFolder(folder: string): Promise<boolean> {
  const res = await apiClient.post<{ folder: string }>('/api/media/folders', { folder });
  return res.success;
}

/**
 * Upload a file via multipart/form-data (`file`, optional `altText`, optional `folder`).
 */
export async function uploadMedia(
  file: File,
  altText = '',
  folder = ''
): Promise<UploadMediaResult> {
  const form = new FormData();
  form.append('file', file);
  form.append('altText', altText);
  form.append('folder', folder);

  const res = await apiClient.post<MediaFile>('/api/media/upload', form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });

  if (res.success && res.data) {
    return { ok: true, media: res.data };
  }

  return { ok: false, error: res.error ?? 'Upload failed.' };
}

/**
 * Update media metadata (alt text, title).
 */
export async function updateMediaMetadata(
  path: string,
  metadata: { altText?: string; title?: string }
): Promise<boolean> {
  const res = await apiClient.patch<MediaFile>(
    `/api/media/${encodeURIComponent(path)}`,
    metadata
  );

  return res.success;
}

/** Backward-compatible alias for alt-only updates. */
export async function updateMediaAlt(path: string, altText: string): Promise<boolean> {
  return updateMediaMetadata(path, { altText });
}

/** Delete a media file by its storage path. */
export async function deleteMedia(path: string): Promise<boolean> {
  const res = await apiClient.delete(`/api/media/${encodeURIComponent(path)}`);
  return res.success;
}

/** Bulk delete media files by storage paths. */
export async function bulkDeleteMedia(paths: string[]): Promise<number> {
  if (paths.length === 0) {
    return 0;
  }

  const res = await apiClient.post<{ deleted: number }>('/api/media/bulk-delete', { paths });
  return res.success && typeof res.data?.deleted === 'number' ? res.data.deleted : 0;
}

export interface StockImageTopic {
  id: string;
  label: string;
  count: number;
}

/** Available stock image topics (flat-file catalog). */
export async function listStockImageTopics(): Promise<StockImageTopic[]> {
  const res = await apiClient.get<StockImageTopic[]>('/api/media/stock-topics');
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export type ImportStockImageResult =
  | { ok: true; media: MediaFile }
  | { ok: false; error: string };

/**
 * Import a random stock image from the topic-aware catalog into Media Library.
 * Topic defaults to admin setting media.stockImageTopic when omitted.
 */
export async function importStockImage(
  topic = '',
  folder = ''
): Promise<ImportStockImageResult> {
  const res = await apiClient.post<MediaFile>('/api/media/stock-import', { topic, folder });

  if (res.success && res.data) {
    return { ok: true, media: res.data };
  }

  return { ok: false, error: res.error ?? 'Stock import failed.' };
}
