const PREFIX_BY_PLATFORM: Record<string, string> = {
  telegram: 'https://t.me/',
  whatsapp: 'https://wa.me/',
  messenger: 'https://m.me/',
  facebook: 'https://www.facebook.com/',
  twitter: 'https://x.com/',
  linkedin: 'https://www.linkedin.com/in/',
  instagram: 'https://www.instagram.com/',
  github: 'https://github.com/',
  website: 'https://',
  email: '',
};

export function socialProfilePrefix(platform: string): string {
  return PREFIX_BY_PLATFORM[platform] ?? 'https://';
}

export function isBareSocialPrefix(url: string): boolean {
  const trimmed = url.trim();
  if (trimmed === '' || trimmed === 'https://' || trimmed === 'http://') {
    return true;
  }

  return Object.values(PREFIX_BY_PLATFORM).some(
    (prefix) => prefix !== '' && (trimmed === prefix || trimmed === prefix.replace(/\/$/, ''))
  );
}

export function suggestSocialProfileUrl(
  nextPlatform: string,
  currentPlatform: string,
  currentUrl: string
): string {
  const prefix = socialProfilePrefix(nextPlatform);
  const trimmed = currentUrl.trim();
  if (nextPlatform === currentPlatform && trimmed !== '' && !isBareSocialPrefix(trimmed)) {
    return currentUrl;
  }

  return prefix;
}
