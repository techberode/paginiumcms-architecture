// frontend/src/components/frontend/FooterSocialLinks.tsx
import React from 'react';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { SocialBrandLink } from '../ui/SocialBrandIcon';
import { safeOptionalHref } from '../../security/safeUrl';

const EMAIL_PATTERN = /^[^\s@:/?#]+@[^\s@:/?#]+$/;

function socialHref(platform: string, url: string): string | undefined {
  if (platform === 'email') {
    const address = url.trim().replace(/^mailto:/i, '');
    return EMAIL_PATTERN.test(address) ? `mailto:${address}` : undefined;
  }
  return safeOptionalHref(url);
}

export const FooterSocialLinks: React.FC<{ className?: string }> = ({ className = '' }) => {
  const { settings } = useSettingsContext();
  const { t } = useI18n();
  const social = settings.social;
  const openInNewTab = settings.ui?.openLinksInNewTab === true;

  if (!social?.enabled || !social.links?.length) {
    return null;
  }

  return (
    <div className={className}>
      <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-3">
        {t('public.footer.socialTitle')}
      </h4>
      <nav
        className="flex flex-wrap items-center justify-start gap-2"
        aria-label={t('public.footer.socialAria')}
      >
        {social.links.map((link) => {
          const href = socialHref(link.platform, link.url);
          if (href === undefined) {
            return null;
          }
          const external = link.platform !== 'email';

          return (
            <SocialBrandLink
              key={`${link.platform}-${link.url}`}
              platform={link.platform}
              href={href}
              target={external && openInNewTab ? '_blank' : undefined}
              rel={external ? 'noopener noreferrer' : undefined}
              title={link.label}
            />
          );
        })}
      </nav>
    </div>
  );
};

export default FooterSocialLinks;
