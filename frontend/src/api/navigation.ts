// frontend/src/api/navigation.ts
import apiClient from './client';

export type NavigationIconType = 'none' | 'lucide' | 'media';
export type NavigationThumbnailSize = 'sm' | 'md' | 'lg';

export interface NavigationItem {
  id: string;
  label: string;
  path: string;
  target?: string;
  order: number;
  parentId?: string | null;
  description?: string;
  iconType?: NavigationIconType;
  iconValue?: string | null;
  previewOnHover?: boolean;
  previewScale?: number;
  thumbnailSize?: NavigationThumbnailSize;
  enabled?: boolean;
}

export async function getNavigation(): Promise<NavigationItem[]> {
  const res = await apiClient.get<NavigationItem[]>('/api/navigation');
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export async function getAdminNavigation(): Promise<NavigationItem[]> {
  const res = await apiClient.get<NavigationItem[]>('/api/admin/navigation');
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export async function updateNavigation(items: NavigationItem[]): Promise<NavigationItem[]> {
  const res = await apiClient.put<NavigationItem[]>('/api/admin/navigation', { items });
  return res.success && Array.isArray(res.data) ? res.data : items;
}

export async function getSecondaryNavigation(): Promise<NavigationItem[]> {
  const res = await apiClient.get<NavigationItem[]>('/api/navigation/secondary');
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export async function getAdminSecondaryNavigation(): Promise<NavigationItem[]> {
  const res = await apiClient.get<NavigationItem[]>('/api/admin/navigation/secondary');
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export async function updateSecondaryNavigation(items: NavigationItem[]): Promise<NavigationItem[]> {
  const res = await apiClient.put<NavigationItem[]>('/api/admin/navigation/secondary', { items });
  return res.success && Array.isArray(res.data) ? res.data : items;
}
