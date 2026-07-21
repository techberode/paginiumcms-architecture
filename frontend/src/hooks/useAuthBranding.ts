// frontend/src/hooks/useAuthBranding.ts
import { useMemo } from 'react';
import type { CSSProperties } from 'react';
import { useSettingsContext } from '../context/SettingsContext';

const DEFAULT_BULLETS = [
  'Bezpečné prihlásenie do administrácie',
  'Správa stránok, článkov a médií',
  'Flat-file úložisko bez SQL databázy',
];

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
  if (value.startsWith('/')) {
    return `${typeof window !== 'undefined' ? window.location.origin : ''}${value}`;
  }
  return value;
}

export function useAuthBranding(): AuthBranding {
  const { settings } = useSettingsContext();

  return useMemo(() => {
    const login = settings.login;
    const title = (login?.pageTitle ?? '').trim() || settings.general.siteName || 'PaginiumCMS';
    const description =
      (login?.pageDescription ?? '').trim() ||
      settings.general.siteDescription ||
      'Správa obsahu a nastavení vášho webu na jednom mieste.';

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

    return {
      title,
      description,
      bullets: bullets.length > 0 ? bullets : DEFAULT_BULLETS,
      backgroundStyle,
    };
  }, [settings]);
}
