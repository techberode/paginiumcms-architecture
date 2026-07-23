import { useEffect } from 'react';
import { useSettingsContext } from '../../context/SettingsContext';
import { resolveBrandingUrl } from '../../utils/brandingUrl';

function upsertLink(rel: string, href: string): void {
  let link = document.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`);
  if (!link) {
    link = document.createElement('link');
    link.rel = rel;
    document.head.appendChild(link);
  }
  link.href = href;
}

/** Applies favicon from public settings to document head. */
export function SiteBrandingHead(): null {
  const { settings } = useSettingsContext();
  const faviconUrl = resolveBrandingUrl(settings?.branding?.faviconUrl);

  useEffect(() => {
    if (faviconUrl) {
      upsertLink('icon', faviconUrl);
      upsertLink('shortcut icon', faviconUrl);
    }
  }, [faviconUrl]);

  useEffect(() => {
    document.documentElement.lang = settings?.general?.language === 'en' ? 'en' : 'sk';
  }, [settings?.general?.language]);

  return null;
}

export default SiteBrandingHead;
