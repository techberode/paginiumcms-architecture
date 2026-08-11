// frontend/src/components/frontend/Footer.tsx
import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Rocket, ShieldCheck, Zap, Heart, ExternalLink } from 'lucide-react';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { FooterNewsletter } from './FooterNewsletter';
import { FooterSocialLinks } from './FooterSocialLinks';
import { LOGO_FALLBACK } from '../../theme/publicUiClasses';
import { useCookieConsentOptional } from '../../context/CookieConsentContext';
import { resolveCookiePolicyHref } from '../../utils/cookiePolicyUrl';

export const Footer: React.FC = () => {
  const { navigation, siteTitle, siteTagline, footerText } = usePublicSite();
  const { settings } = useSettingsContext();
  const { t } = useI18n();
  const navigate = useNavigate();
  const demoUrl = settings.demo?.url ?? 'https://demo.paginiumcms.com';
  const isDemoInstance = settings.demo?.enabled === true;
  const showDemoFooterLink = !isDemoInstance && settings.demo?.showFooterLink !== false;
  const footerNewsletterEnabled = settings.newsletter?.footerEnabled === true;
  const cookieConsent = useCookieConsentOptional();
  const cookiePolicy = resolveCookiePolicyHref(settings.privacy?.cookiePolicyUrl ?? '');
  const cmsVersion = settings.cmsInfo?.version?.trim() ?? '';

  const sortedNav = [...navigation].sort((a, b) => a.order - b.order);

  return (
    <footer className="public-footer transition-colors pt-16 pb-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-12 pb-16 border-b border-white/10">
          <div className="md:col-span-1">
            <div className="flex items-center gap-3 mb-4">
              <div className={`w-9 h-9 ${LOGO_FALLBACK}`}>
                <Rocket className="w-5 h-5 text-theme-primary-foreground" />
              </div>
              <div className="min-w-0">
                <span className="font-extrabold text-xl tracking-tight public-footer-heading block">{siteTitle}</span>
                {cmsVersion !== '' ? (
                  <span className="mt-1 inline-flex text-[11px] font-semibold uppercase tracking-wide opacity-70 public-footer-heading">
                    {t('public.footer.cmsVersion', { version: cmsVersion })}
                  </span>
                ) : null}
              </div>
            </div>
            <p className="text-sm leading-relaxed opacity-80">{siteTagline}</p>
            <div className="mt-6">
              <span className="inline-flex items-center gap-1 text-xs bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-1 rounded-full font-semibold">
                <ShieldCheck className="w-3.5 h-3.5" /> {t('public.footer.secureBadge')}
              </span>
            </div>
            <FooterSocialLinks className="mt-6" />
          </div>

          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-4">{t('public.footer.quickLinks')}</h4>
            <ul className="space-y-2.5">
              {sortedNav.map((item) => (
                <li key={item.id}>
                  <button
                    type="button"
                    onClick={() => navigate(item.path)}
                    className="text-sm public-footer-link cursor-pointer"
                  >
                    {item.label}
                  </button>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-4">{t('public.footer.architectureTitle')}</h4>
            <ul className="space-y-3 text-sm opacity-80">
              <li className="flex items-center gap-2">
                <Zap className="w-4 h-4 text-theme-accent shrink-0" />
                <span>{t('public.footer.architectureFlatFile')}</span>
              </li>
              <li className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                <span>{t('public.footer.architectureAuth')}</span>
              </li>
            </ul>
          </div>

          <div>
            {footerNewsletterEnabled ? <FooterNewsletter /> : null}
            {showDemoFooterLink ? (
              <div className={footerNewsletterEnabled ? 'mt-6 pt-6 border-t border-white/10' : undefined}>
                <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-4">
                  {t('public.footer.tryCmsTitle')}
                </h4>
                <div className="space-y-3">
                  <p className="text-sm opacity-70 leading-relaxed">{t('public.footer.tryCmsBody')}</p>
                  <a
                    href={demoUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-2 text-sm font-semibold public-footer-link text-theme-accent"
                  >
                    demo.paginiumcms.com
                    <ExternalLink className="w-3.5 h-3.5" aria-hidden="true" />
                  </a>
                </div>
              </div>
            ) : null}
            {isDemoInstance && !footerNewsletterEnabled ? (
              <>
                <h4 className="text-xs font-bold uppercase tracking-wider public-footer-heading mb-4">
                  {t('public.footer.tryCmsTitle')}
                </h4>
                <p className="text-sm opacity-70 leading-relaxed">{t('public.footer.demoInstanceBody')}</p>
              </>
            ) : null}
          </div>
        </div>

        <div className="mt-12 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs opacity-70">
          <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span>{footerText}</span>
            {cookieConsent?.bannerEnabled && cookieConsent.decided ? (
              <button
                type="button"
                onClick={cookieConsent.openSettings}
                className="public-footer-cookie-link font-medium"
              >
                {t('public.footer.cookieSettings')}
              </button>
            ) : null}
            {cookiePolicy.external ? (
              <a
                href={cookiePolicy.href}
                target="_blank"
                rel="noopener noreferrer"
                className="public-footer-cookie-link font-medium"
              >
                {t('public.footer.cookiePolicy')}
              </a>
            ) : (
              <Link to={cookiePolicy.href} className="public-footer-cookie-link font-medium">
                {t('public.footer.cookiePolicy')}
              </Link>
            )}
          </div>
          <div className="flex items-center gap-1 shrink-0">
            <span>{t('public.footer.madeWith')}</span>
            <Heart className="w-3.5 h-3.5 text-rose-500 fill-rose-500" />
            <span>{t('public.footer.forCreators')}</span>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
