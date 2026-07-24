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
}

export async function getNavigation(): Promise<NavigationItem[]> {
  const res = await apiClient.get<NavigationItem[]>('/api/navigation');
  return res.success && Array.isArray(res.data) ? res.data : [];
}

export async function updateNavigation(items: NavigationItem[]): Promise<NavigationItem[]> {
  const res = await apiClient.put<NavigationItem[]>('/api/admin/navigation', { items });
  return res.success && Array.isArray(res.data) ? res.data : items;
}
