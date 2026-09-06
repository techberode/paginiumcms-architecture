// frontend/src/api/media.ts
// === Media API (Iteration 8 + 24 DAM) ===
// Typed calls to backend /api/media/*. Backend is the single source of truth.
import apiClient from './client';
import {
  resolveAdminMediaFileUrl,
  resolveApiBaseUrl,
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
  imageOptimization?: ImageOptimizationCapabilities;
}

export interface ImageOptimizationCapabilities {
  available: boolean;
  jpeg: boolean;
  png: boolean;
  webp: boolean;
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

/** Preset widths for on-demand `/storage/...?w=` thumbnails (backend GD). */
export const MEDIA_THUMB_WIDTH = {
  card: 480,
  hero: 960,
  avatar: 128,
  gallery: 640,
} as const;

/** Append width query for server-side thumbnail generation. */
export function appendThumbnailQuery(url: string, width: number): string {
  if (!url || width <= 0) {
    return url;
  }

  if ((url.startsWith('http://') || url.startsWith('https://')) && !url.includes('/storage/')) {
    return url;
  }

  const separator = url.includes('?') ? '&' : '?';

  return `${url}${separator}w=${width}`;
}

/** Same-origin storage URL with optional thumbnail width. */
export function resolvePublicMediaThumbnailUrl(url: string, width: number): string {
  return appendThumbnailQuery(resolvePublicMediaUrl(url), width);
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

const OPTIMIZABLE_MIME_TYPES = new Set([
  'image/jpeg',
  'image/jpg',
  'image/png',
  'image/webp',
]);

/** Raster images that can be re-encoded at the same resolution to save bytes. */
export function isOptimizableMedia(
  file: MediaFile,
  capabilities?: ImageOptimizationCapabilities
): boolean {
  if (capabilities !== undefined && !capabilities.available) {
    return false;
  }

  const mime = file.mimeType.toLowerCase();
  if (!OPTIMIZABLE_MIME_TYPES.has(mime)) {
    return false;
  }

  if (capabilities === undefined) {
    return true;
  }

  if (mime === 'image/jpeg' || mime === 'image/jpg') {
    return capabilities.jpeg;
  }
  if (mime === 'image/png') {
    return capabilities.png;
  }
  if (mime === 'image/webp') {
    return capabilities.webp;
  }

  return false;
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
    imageOptimization: {
      available: false,
      jpeg: false,
      png: false,
      webp: false,
    },
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

  const res = await apiClient.post<MediaFile>('/api/media/upload', form);

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

export interface OptimizeMediaOptions {
  targetWidth?: number;
  targetHeight?: number;
}

export interface MediaImageInfo {
  width: number;
  height: number;
  mimeType: string;
  sizeBytes: number;
}

export interface OptimizeMediaPayload {
  media: MediaFile;
  beforeBytes: number;
  afterBytes: number;
  savedBytes: number;
  savedPercent: number;
  beforeWidth: number;
  beforeHeight: number;
  width: number;
  height: number;
}

export type OptimizeMediaResult =
  | { ok: true; data: OptimizeMediaPayload }
  | { ok: false; error: string };

export interface OptimizePreviewPayload {
  previewToken: string;
  beforeBytes: number;
  afterBytes: number;
  savedBytes: number;
  savedPercent: number;
  beforeWidth: number;
  beforeHeight: number;
  width: number;
  height: number;
}

export type OptimizePreviewResult =
  | { ok: true; data: OptimizePreviewPayload }
  | { ok: false; error: string };

/** Same-origin URL for a short-lived optimized preview image. */
export function resolveOptimizePreviewUrl(previewToken: string): string {
  return `${resolveApiBaseUrl()}/api/media/optimize-preview/${encodeURIComponent(previewToken)}`;
}

/**
 * Load raster dimensions and size for a media file.
 */
export async function getMediaImageInfo(path: string): Promise<MediaImageInfo | null> {
  const res = await apiClient.get<MediaImageInfo>(
    `/api/media/${encodeURIComponent(path)}/image-info`
  );

  return res.success && res.data ? res.data : null;
}

/** Scale dimensions proportionally when one axis changes. */
export function scaleMediaDimensions(
  originalWidth: number,
  originalHeight: number,
  changedAxis: 'width' | 'height',
  newValue: number
): { width: number; height: number } {
  if (originalWidth <= 0 || originalHeight <= 0 || newValue <= 0) {
    return { width: originalWidth, height: originalHeight };
  }

  if (changedAxis === 'width') {
    const width = Math.max(1, Math.min(originalWidth, Math.round(newValue)));
    const height = Math.max(1, Math.round((originalHeight * width) / originalWidth));
    return { width, height };
  }

  const height = Math.max(1, Math.min(originalHeight, Math.round(newValue)));
  const width = Math.max(1, Math.round((originalWidth * height) / originalHeight));
  return { width, height };
}

/**
 * Re-encode a raster image; optional proportional downscale via targetWidth/targetHeight.
 */
export async function optimizeMedia(
  path: string,
  options: OptimizeMediaOptions = {}
): Promise<OptimizeMediaResult> {
  const body: OptimizeMediaOptions = {};
  if (options.targetWidth !== undefined && options.targetWidth > 0) {
    body.targetWidth = options.targetWidth;
  }
  if (options.targetHeight !== undefined && options.targetHeight > 0) {
    body.targetHeight = options.targetHeight;
  }

  const res = await apiClient.post<OptimizeMediaPayload>(
    `/api/media/${encodeURIComponent(path)}/optimize`,
    body
  );

  if (res.success && res.data) {
    return { ok: true, data: res.data };
  }

  return { ok: false, error: res.error ?? 'Optimization failed.' };
}

/**
 * Generate optimization preview without saving (returns preview token + stats).
 */
export async function previewOptimizeMedia(
  path: string,
  options: OptimizeMediaOptions = {}
): Promise<OptimizePreviewResult> {
  const body: OptimizeMediaOptions = {};
  if (options.targetWidth !== undefined && options.targetWidth > 0) {
    body.targetWidth = options.targetWidth;
  }
  if (options.targetHeight !== undefined && options.targetHeight > 0) {
    body.targetHeight = options.targetHeight;
  }

  const res = await apiClient.post<OptimizePreviewPayload>(
    `/api/media/${encodeURIComponent(path)}/optimize/preview`,
    body
  );

  if (res.success && res.data) {
    return { ok: true, data: res.data };
  }

  return { ok: false, error: res.error ?? 'Optimization preview failed.' };
}

/**
 * Persist a previously generated optimization preview.
 */
export async function applyOptimizeMedia(
  path: string,
  previewToken: string
): Promise<OptimizeMediaResult> {
  const res = await apiClient.post<OptimizeMediaPayload>(
    `/api/media/${encodeURIComponent(path)}/optimize/apply`,
    { previewToken }
  );

  if (res.success && res.data) {
    return { ok: true, data: res.data };
  }

  return { ok: false, error: res.error ?? 'Optimization apply failed.' };
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
