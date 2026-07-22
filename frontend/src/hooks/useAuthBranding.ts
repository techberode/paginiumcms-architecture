// frontend/src/hooks/useAuthBranding.ts
import { useMemo } from 'react';
import type { CSSProperties } from 'react';
import { resolveAdminMediaPreviewUrl, resolvePublicMediaUrl } from '../api/media';
import { useSettingsContext } from '../context/SettingsContext';
import { useI18n } from '../context/I18nContext';

export interface AuthBranding {
  title: string;
  description: string;
  bullets: string[];
  backgroundStyle: CSSProperties;
}

function resolveBackgroundUrl(raw: string | undefined): string {
  const value = (raw ?? '').trim();
  if (!value) {
    return '';
  }
  if (/^https?:\/\//i.test(value) || value.startsWith('data:')) {
    return value;
  }
  if (value.startsWith('/storage/')) {
    return resolvePublicMediaUrl(value);
  }
  if (value.startsWith('media/')) {
    return resolveAdminMediaPreviewUrl(value);
  }
  if (value.startsWith('/')) {
    return `${typeof window !== 'undefined' ? window.location.origin : ''}${value}`;
  }
  return value;
}

export function useAuthBranding(): AuthBranding {
  const { settings } = useSettingsContext();
  const { t } = useI18n();

  return useMemo(() => {
    const login = settings.login;
    const title =
      (login?.pageTitle ?? '').trim() ||
      settings.general.siteName ||
      t('public.defaults.siteName');
    const description =
      (login?.pageDescription ?? '').trim() ||
      settings.general.siteDescription ||
      t('public.auth.branding.defaultDescription');

    const bullets = (login?.infoBullets ?? '')
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean);

    const backgroundUrl = resolveBackgroundUrl(login?.backgroundImageUrl);
    const backgroundStyle: CSSProperties = backgroundUrl
      ? {
          backgroundImage: `linear-gradient(135deg, rgba(15,23,42,0.88), rgba(49,46,129,0.75)), url("${backgroundUrl}")`,
          backgroundSize: 'cover',
          backgroundPosition: 'center',
        }
      : {};

    const defaultBullets = [
      t('public.auth.branding.defaultBullets.secureLogin'),
      t('public.auth.branding.defaultBullets.contentManagement'),
      t('public.auth.branding.defaultBullets.flatFile'),
    ];

    return {
      title,
      description,
      bullets: bullets.length > 0 ? bullets : defaultBullets,
      backgroundStyle,
    };
  }, [settings, t]);
}
