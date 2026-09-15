// frontend/src/utils/socialLinkIcons.tsx
import {
  Github,
  Gitlab,
  Twitter,
  Facebook,
  Instagram,
  Linkedin,
  Youtube,
  Globe,
  Mail,
  Rss,
  MessageCircle,
  Phone,
  Send,
  Share2,
  type LucideIcon,
} from 'lucide-react';

export type SocialPlatform =
  | 'github'
  | 'gitlab'
  | 'twitter'
  | 'facebook'
  | 'instagram'
  | 'linkedin'
  | 'youtube'
  | 'mastodon'
  | 'discord'
  | 'website'
  | 'email'
  | 'rss';

export const SOCIAL_PLATFORMS: SocialPlatform[] = [
  'github',
  'gitlab',
  'twitter',
  'facebook',
  'instagram',
  'linkedin',
  'youtube',
  'mastodon',
  'discord',
  'website',
  'email',
  'rss',
];

const ICONS: Record<string, LucideIcon> = {
  github: Github,
  gitlab: Gitlab,
  twitter: Twitter,
  x: Twitter,
  facebook: Facebook,
  instagram: Instagram,
  linkedin: Linkedin,
  youtube: Youtube,
  mastodon: Share2,
  discord: MessageCircle,
  website: Globe,
  email: Mail,
  rss: Rss,
  telegram: Send,
  whatsapp: Phone,
  messenger: MessageCircle,
};

const BRAND_COLORS: Record<string, string> = {
  github: '#181717',
  gitlab: '#FC6D26',
  twitter: '#1D9BF0',
  x: '#111111',
  facebook: '#1877F2',
  instagram: '#E4405F',
  linkedin: '#0A66C2',
  youtube: '#FF0000',
  mastodon: '#6364FF',
  discord: '#5865F2',
  website: '#0EA5E9',
  email: '#EA4335',
  rss: '#F26522',
  telegram: '#26A5E4',
  whatsapp: '#25D366',
  messenger: '#0084FF',
};

export function socialPlatformIcon(platform: string): LucideIcon {
  return ICONS[platform] ?? Globe;
}

export function socialBrandColor(platform: string): string {
  return BRAND_COLORS[platform] ?? '#748194';
}

export function socialBrandTint(platform: string, strong = false): string {
  const color = socialBrandColor(platform);
  return `${color}${strong ? '26' : '16'}`;
}

export function isSocialPlatform(value: string): value is SocialPlatform {
  return SOCIAL_PLATFORMS.includes(value as SocialPlatform);
}

export interface SocialLinkItem {
  id: string;
  platform: SocialPlatform;
  url: string;
  label: string;
  enabled: boolean;
}

export function parseSocialLinksJson(raw: string | undefined | null): SocialLinkItem[] {
  if (!raw || raw.trim() === '') {
    return [];
  }
  try {
    const parsed = JSON.parse(raw) as unknown;
    if (!Array.isArray(parsed)) {
      return [];
    }
    return parsed
      .filter((item): item is Record<string, unknown> => typeof item === 'object' && item !== null)
      .map((item, index) => {
        const platform = String(item.platform ?? 'website');
        const safePlatform = isSocialPlatform(platform) ? platform : 'website';
        let url = String(item.url ?? '').trim();
        if (safePlatform === 'email' && url.startsWith('mailto:')) {
          url = url.slice(7);
        }
        return {
          id: String(item.id ?? `${safePlatform}-${index}`),
          platform: safePlatform,
          url,
          label: String(item.label ?? '').trim(),
          enabled: item.enabled !== false,
        };
      });
  } catch {
    return [];
  }
}

export function serializeSocialLinksJson(links: SocialLinkItem[]): string {
  return JSON.stringify(
    links.map((link) => {
      let url = link.url.trim();
      if (link.platform === 'email' && url !== '' && !url.startsWith('mailto:')) {
        url = url.includes('@') ? url : url;
      }
      return {
        id: link.id,
        platform: link.platform,
        url: link.platform === 'email' && !url.startsWith('mailto:') ? url : url,
        label: link.label,
        enabled: link.enabled,
      };
    })
  );
}

export function defaultSocialLinks(): SocialLinkItem[] {
  return [
    {
      id: 'github-main',
      platform: 'github',
      url: 'https://github.com/techberode/paginiumcms-architecture',
      label: 'GitHub',
      enabled: true,
    },
  ];
}

export function resolveSocialHref(link: SocialLinkItem): string {
  if (link.platform === 'email') {
    return link.url.startsWith('mailto:') ? link.url : `mailto:${link.url}`;
  }
  return link.url;
}
