import type { PublicSettings } from '../api/settings';

export function isDefaultSiteName(name: string): boolean {
  const trimmed = name.trim();
  if (trimmed.length < 2) {
    return true;
  }
  const normalized = trimmed
    .normalize('NFD')
    .replace(/\p{M}/gu, '')
    .toLowerCase()
    .replace(/\s+/g, '');
  return normalized === 'paginiumcms';
}

/** Mirrors backend GettingStartedStatusService for fallback when dashboard probes are missing. */
export function isSiteBrandingConfigured(settings: PublicSettings): boolean {
  const siteName = (settings.general?.siteName ?? '').trim();
  if (!isDefaultSiteName(siteName)) {
    return true;
  }
  const description = (settings.general?.siteDescription ?? '').trim();
  if (description.length >= 3) {
    return true;
  }
  const logo = (settings.branding?.logoUrl ?? '').trim();
  const favicon = (settings.branding?.faviconUrl ?? '').trim();
  return logo !== '' || favicon !== '';
}

export interface GettingStartedProbes {
  siteName?: boolean;
  mail?: boolean;
}

export function resolveGettingStartedDone(
  probes: GettingStartedProbes | undefined,
  settings: PublicSettings,
  key: 'siteName' | 'mail'
): boolean {
  const fromServer = probes?.[key];
  if (typeof fromServer === 'boolean') {
    return fromServer;
  }
  if (key === 'siteName') {
    return isSiteBrandingConfigured(settings);
  }
  return false;
}
