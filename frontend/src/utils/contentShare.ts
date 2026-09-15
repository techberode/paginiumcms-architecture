import { resolveBoolSetting } from './contentPublicSettings';

export type ContentShareNetwork = 'facebook' | 'x' | 'linkedin' | 'email' | 'copy';

export interface ContentShareSettings {
  enabled: boolean;
  onArticles: boolean;
  onPages: boolean;
  networks: Record<ContentShareNetwork, boolean>;
}

export function resolveContentShareSettings(
  contentSettings: Record<string, unknown> | undefined
): ContentShareSettings {
  return {
    enabled: resolveBoolSetting(contentSettings?.shareEnabled, true),
    onArticles: resolveBoolSetting(contentSettings?.shareOnArticles, true),
    onPages: resolveBoolSetting(contentSettings?.shareOnPages, false),
    networks: {
      facebook: resolveBoolSetting(contentSettings?.shareFacebook, true),
      x: resolveBoolSetting(contentSettings?.shareX, true),
      linkedin: resolveBoolSetting(contentSettings?.shareLinkedin, true),
      email: resolveBoolSetting(contentSettings?.shareEmail, true),
      copy: resolveBoolSetting(contentSettings?.shareCopy, true),
    },
  };
}

export function contentShareHref(
  network: Exclude<ContentShareNetwork, 'copy'>,
  url: string,
  title: string
): string {
  const encodedUrl = encodeURIComponent(url);
  const encodedTitle = encodeURIComponent(title);
  if (network === 'facebook') {
    return `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
  }
  if (network === 'x') {
    return `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
  }
  if (network === 'linkedin') {
    return `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`;
  }
  return `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
}

export function visibleShareNetworks(settings: ContentShareSettings): ContentShareNetwork[] {
  if (!settings.enabled) {
    return [];
  }
  return (Object.keys(settings.networks) as ContentShareNetwork[]).filter((key) => settings.networks[key]);
}

export function shouldShowContentShare(
  settings: ContentShareSettings,
  surface: 'article' | 'page',
  options?: { isHome?: boolean }
): boolean {
  if (!settings.enabled || visibleShareNetworks(settings).length === 0) {
    return false;
  }
  if (surface === 'article') {
    return settings.onArticles;
  }
  if (options?.isHome) {
    return false;
  }
  return settings.onPages;
}
