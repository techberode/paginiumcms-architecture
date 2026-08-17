export type BlogSidebarPlacement = 'left' | 'right';

export interface BlogSidebarSettings {
  enabled: boolean;
  placement: BlogSidebarPlacement;
  showTags: boolean;
  showCategories: boolean;
  showLatest: boolean;
  showPopular: boolean;
}

export function resolveBlogSidebarSettings(
  contentSettings: Record<string, unknown> | undefined
): BlogSidebarSettings {
  const placementRaw = String(contentSettings?.blogSidebarPlacement ?? 'right');

  return {
    enabled: contentSettings?.blogSidebarEnabled === true,
    placement: placementRaw === 'left' ? 'left' : 'right',
    showTags: contentSettings?.blogSidebarShowTags !== false,
    showCategories: contentSettings?.blogSidebarShowCategories !== false,
    showLatest: contentSettings?.blogSidebarShowLatest !== false,
    showPopular: contentSettings?.blogSidebarShowPopular !== false,
  };
}
