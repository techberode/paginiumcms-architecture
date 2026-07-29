import apiClient from './client';

export type GalleryItemStatus = 'draft' | 'published';

export interface GalleryItem {
  id: string;
  title: string;
  description: string;
  mediaPath: string;
  featureTag?: string | null;
  linkUrl?: string | null;
  sortOrder: number;
  status: GalleryItemStatus;
  publishedAt?: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface GalleryAdminListResponse {
  items: GalleryItem[];
  count: number;
}

export interface GalleryPublicResponse {
  items: GalleryItem[];
  count: number;
}

export type GalleryItemPayload = {
  title: string;
  description?: string;
  mediaPath: string;
  featureTag?: string;
  linkUrl?: string;
  status?: GalleryItemStatus;
  sortOrder?: number;
};

export async function listAdminGalleryItems(): Promise<GalleryItem[]> {
  const res = await apiClient.get<GalleryAdminListResponse>('/api/admin/gallery');
  return res.success && res.data?.items ? res.data.items : [];
}

export async function createGalleryItem(
  payload: GalleryItemPayload
): Promise<{ ok: true; item: GalleryItem } | { ok: false; error: string }> {
  const res = await apiClient.post<GalleryItem>('/api/admin/gallery', payload);
  if (res.success && res.data) {
    return { ok: true, item: res.data };
  }
  return { ok: false, error: res.error ?? 'Failed to create gallery item.' };
}

export async function updateGalleryItem(
  id: string,
  payload: Partial<GalleryItemPayload>
): Promise<{ ok: true; item: GalleryItem } | { ok: false; error: string }> {
  const res = await apiClient.put<GalleryItem>(`/api/admin/gallery/${encodeURIComponent(id)}`, payload);
  if (res.success && res.data) {
    return { ok: true, item: res.data };
  }
  return { ok: false, error: res.error ?? 'Failed to update gallery item.' };
}

export async function deleteGalleryItem(
  id: string
): Promise<{ ok: true } | { ok: false; error: string }> {
  const res = await apiClient.delete<{ deleted: boolean }>(`/api/admin/gallery/${encodeURIComponent(id)}`);
  if (res.success) {
    return { ok: true };
  }
  return { ok: false, error: res.error ?? 'Failed to delete gallery item.' };
}

export async function reorderGalleryItems(
  ids: string[]
): Promise<{ ok: true } | { ok: false; error: string }> {
  const res = await apiClient.put<{ ids: string[] }>('/api/admin/gallery/reorder', { ids });
  if (res.success) {
    return { ok: true };
  }
  return { ok: false, error: res.error ?? 'Failed to reorder gallery items.' };
}

export async function listPublicGalleryItems(): Promise<GalleryItem[]> {
  const res = await apiClient.get<GalleryPublicResponse>('/api/gallery/public');
  return res.success && res.data?.items ? res.data.items : [];
}
