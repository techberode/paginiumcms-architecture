import React, { useMemo } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { usePublicSite } from '../../context/PublicSiteContext';
import { isHomeTemplatePage, resolveSiteHomePage } from '../../utils/siteHomePage';

export const PublicBreadcrumbs: React.FC = () => {
  const { t } = useI18n();
  const { pathname } = useLocation();
  const { settings } = useSettingsContext();
  const { getPageBySlug, getArticleBySlug, pages } = usePublicSite();

  const enabled = settings?.layout?.breadcrumbsEnabled !== false;
  const onHome = settings?.layout?.breadcrumbsOnHome === true;

  const crumbs = useMemo(() => {
    if (!enabled) {
      return [];
    }

    if (pathname === '/' && !onHome) {
      return [];
    }

    const home = { label: t('public.nav.home'), to: '/' };

    if (pathname === '/') {
      return [home];
    }

    if (pathname.startsWith('/blog/')) {
      const slug = pathname.split('/')[2] ?? '';
      const article = slug ? getArticleBySlug(slug) : undefined;
      const title = article?.title ?? slug;
      return [
        home,
        { label: t('public.nav.blog'), to: '/blog' },
        { label: title, to: pathname },
      ];
    }

    if (pathname === '/blog') {
      return [home, { label: t('public.nav.blog'), to: '/blog' }];
    }

    const slug = pathname.replace(/^\/+/, '');
    if (slug === '' || slug.includes('/')) {
      return [home];
    }

    const page = getPageBySlug(slug);
    const siteHome = resolveSiteHomePage(pages);
    if (page && (isHomeTemplatePage(page) || (siteHome && siteHome.slug === page.slug))) {
      return pathname === '/' && onHome ? [home] : [];
    }

    const title = page?.title ?? slug;

    return [home, { label: title, to: pathname }];
  }, [enabled, onHome, pathname, getPageBySlug, getArticleBySlug, pages, t]);

  if (crumbs.length === 0) {
    return null;
  }

  return (
    <nav className="pg-breadcrumbs" aria-label={t('public.breadcrumbs.ariaLabel')}>
      <ol className="pg-breadcrumbs-list">
        {crumbs.map((crumb, index) => {
          const isLast = index === crumbs.length - 1;
          return (
            <li key={`${crumb.to}-${index}`} className="pg-breadcrumbs-item">
              {index > 0 ? (
                <ChevronRight className="pg-breadcrumbs-sep w-3.5 h-3.5" aria-hidden />
              ) : null}
              {isLast ? (
                <span className="pg-breadcrumbs-current" aria-current="page">
                  {crumb.label}
                </span>
              ) : (
                <Link to={crumb.to} className="pg-breadcrumbs-link">
                  {crumb.label}
                </Link>
              )}
            </li>
          );
        })}
      </ol>
    </nav>
  );
};

export default PublicBreadcrumbs;
