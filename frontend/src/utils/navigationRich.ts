import { icons, type LucideIcon } from 'lucide-react';
import type { NavigationIconType, NavigationThumbnailSize } from '../api/navigation';
import { resolvePublicMediaUrl } from '../api/media';

export const NAVIGATION_THUMBNAIL_CLASS: Record<NavigationThumbnailSize, string> = {
  sm: 'w-6 h-6',
  md: 'w-8 h-8',
  lg: 'w-10 h-10',
};

export const NAV_LUCIDE_DEFAULTS = [
  'Home',
  'Mail',
  'Phone',
  'MapPin',
  'FileText',
  'BookOpen',
  'Newspaper',
  'Calendar',
  'Image',
  'Camera',
  'Users',
  'Settings',
  'Info',
  'HelpCircle',
  'Globe',
  'Search',
  'Menu',
  'MessageSquare',
  'Heart',
  'Star',
  'ShoppingBag',
  'Briefcase',
  'Download',
  'Link',
  'Folder',
  'LayoutGrid',
  'Sparkles',
  'Bell',
  'Shield',
  'Lock',
  'Clock',
  'Map',
  'Navigation',
] as const;

let lucideCatalogCache: string[] | null = null;

export function lucideIconCatalog(): string[] {
  if (lucideCatalogCache) {
    return lucideCatalogCache;
  }

  lucideCatalogCache = Object.keys(icons)
    .filter((name) => /^[A-Z]/.test(name) && icons[name as keyof typeof icons] != null)
    .sort((a, b) => a.localeCompare(b));

  return lucideCatalogCache;
}

function lucideSearchHaystack(name: string): string {
  const kebab = name.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
  const spaced = kebab.replace(/-/g, ' ');
  return `${name.toLowerCase()} ${kebab} ${spaced}`;
}

export function filterLucideIconCatalog(query: string, limit = 80): string[] {
  const catalog = lucideIconCatalog();
  const needle = query.trim().toLowerCase().replace(/[\s_]+/g, '-');

  if (needle === '') {
    return NAV_LUCIDE_DEFAULTS.filter((name) => catalog.includes(name));
  }

  return catalog.filter((name) => lucideSearchHaystack(name).includes(needle)).slice(0, limit);
}

export function normalizeLucideIconName(name: string): string {
  const trimmed = name.trim();
  if (trimmed === '') {
    return '';
  }

  if (trimmed.includes('-')) {
    return trimmed
      .split('-')
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join('');
  }

  return trimmed.charAt(0).toUpperCase() + trimmed.slice(1);
}

export function resolveNavigationIconComponent(name: string | null | undefined): LucideIcon | null {
  if (!name?.trim()) {
    return null;
  }

  const key = normalizeLucideIconName(name);
  if (key === '') {
    return null;
  }

  const icon = (icons as Record<string, LucideIcon | undefined>)[key];

  return icon ?? null;
}

export function resolveNavigationIconUrl(
  iconType: NavigationIconType | undefined,
  iconValue: string | null | undefined
): string | null {
  if (iconType !== 'media' || !iconValue) {
    return null;
  }

  return resolvePublicMediaUrl(iconValue);
}

export function effectivePreviewScale(itemScale: number | undefined, defaultScale: number): number {
  const scale = itemScale ?? defaultScale;
  return Math.min(3, Math.max(1, scale));
}

export function navigationItemHasVisual(
  iconType: NavigationIconType | undefined,
  iconValue: string | null | undefined
): boolean {
  if (!iconType || iconType === 'none' || !iconValue) {
    return false;
  }

  if (iconType === 'lucide') {
    return resolveNavigationIconComponent(iconValue) !== null;
  }

  if (iconType === 'media') {
    return resolveNavigationIconUrl(iconType, iconValue) !== null;
  }

  return false;
}
