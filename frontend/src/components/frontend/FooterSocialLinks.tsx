// frontend/src/components/frontend/FooterSocialLinks.tsx
import React from 'react';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { socialPlatformIcon } from '../../utils/socialLinkIcons';

function socialHref(platform: string, url: string): string {
  if (platform === 'email') {
    return url.startsWith('mailto:') ? url : `mailto:${url}`;
  }
  return url;
}

export const FooterSocialLinks: React.FC = () => {
  const { settings } = useSettingsContext();
  const { t } = useI18n();
  const social = settings.social;
  const openInNewTab = settings.ui?.openLinksInNewTab === true;

  if (!social?.enabled || !social.links?.length) {
    return null;
  }

  return (
    <nav
      className="flex flex-wrap items-center justify-center sm:justify-end gap-2"
      aria-label={t('public.footer.socialAria')}
    >
      {social.links.map((link) => {
        const Icon = socialPlatformIcon(link.platform);
        const href = socialHref(link.platform, link.url);
        const external = link.platform !== 'email';

        return (
          <a
            key={`${link.platform}-${link.url}`}
            href={href}
            target={external && openInNewTab ? '_blank' : undefined}
            rel={external ? 'noopener noreferrer' : undefined}
            title={link.label}
            aria-label={link.label}
            className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 bg-white/5 text-theme-accent transition-colors hover:bg-white/10 hover:text-theme-primary-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-theme-accent"
          >
            <Icon className="h-4 w-4" aria-hidden="true" />
          </a>
        );
      })}
    </nav>
  );
};

export default FooterSocialLinks;
