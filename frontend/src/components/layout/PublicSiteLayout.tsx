// frontend/src/components/layout/PublicSiteLayout.tsx
import React, { useMemo, useState } from 'react';
import { Outlet, useLocation, useNavigate, useParams } from 'react-router-dom';
import { Navbar } from '../frontend/Navbar';
import { Footer } from '../frontend/Footer';
import { SideNav } from '../frontend/SideNav';
import { CMSBar, CMSBarDoc } from '../frontend/CMSBar';
import { SiteSearchModal } from '../frontend/SiteSearchModal';
import { PageRenderer } from '../frontend/PageRenderer';
import { FeatureGallerySection } from '../frontend/FeatureGallerySection';
import { FeaturesPage } from '../frontend/FeaturesPage';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useI18n } from '../../context/I18nContext';
import { useAuth } from '../../hooks/useAuth';
import { useSeoMeta } from '../../hooks/useSeoMeta';
import { DemoPublicStrip } from '../frontend/DemoPublicStrip';
import { MaintenanceGate } from '../maintenance/MaintenanceGate';
import { CookieConsentProvider } from '../../context/CookieConsentContext';
import { CookieConsentBanner } from '../frontend/CookieConsentBanner';
import { BackToTopButton } from '../frontend/BackToTopButton';
import { useAnalyticsPageview } from '../../hooks/useAnalyticsPageview';
import { galleryPublicSlug } from '../../utils/galleryPublicRoute';
import { BTN_PRIMARY, PUBLIC_SPINNER } from '../../theme/publicUiClasses';
import {
  resolveNavigationLayout,
  sideNavBreakpointClass,
} from '../../utils/navigationLayoutSettings';

const ADMIN_PREFIXES = [
  '/dashboard',
  '/pages',
  '/articles',
  '/media',
  '/code-editor',
  '/backups',
  '/trash',
  '/audit',
  '/notifications',
  '/settings',
  '/users',
];

export function PublicHomePage() {
  const { t } = useI18n();
  const { getPageBySlug, loading } = usePublicSite();
  const { settings } = useSettingsContext();
  const home = getPageBySlug('home') ?? getPageBySlug('index');
  const galleryPlacement = settings.gallery?.placement ?? 'route';
  const showGalleryEmbed =
    settings.gallery?.enabled === true &&
    (galleryPlacement === 'home' || galleryPlacement === 'both');

  if (loading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  if (!home) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center px-4 text-center">
        <h1 className="text-3xl font-black text-theme-text">PaginiumCMS</h1>
        <p className="mt-3 text-theme-text-muted">{t('public.layout.noHomePage')}</p>
        {showGalleryEmbed ? <FeatureGallerySection variant="embedded" /> : null}
      </div>
    );
  }

  return (
    <>
      <PageRenderer page={home} />
      {showGalleryEmbed ? <FeatureGallerySection variant="embedded" /> : null}
    </>
  );
}

export function PublicSlugPage() {
  const { t } = useI18n();
  const { slug } = useParams<{ slug: string }>();
  const { getPageBySlug, loading } = usePublicSite();
  const { settings } = useSettingsContext();
  const navigate = useNavigate();

  const galleryEnabled = settings.gallery?.enabled === true;
  const galleryPlacement = settings.gallery?.placement ?? 'route';
  const galleryRouteSlug = galleryPublicSlug(settings.gallery?.publicRoute);
  // Dynamic publicRoute from Settings (single segment); /features has a dedicated App route.
  const isGalleryRoute =
    galleryEnabled &&
    (galleryPlacement === 'route' || galleryPlacement === 'both') &&
    slug === galleryRouteSlug &&
    galleryRouteSlug !== 'features';

  if (isGalleryRoute) {
    return <FeaturesPage />;
  }

  const page = slug ? getPageBySlug(slug) : undefined;

  if (loading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className={PUBLIC_SPINNER} />
      </div>
    );
  }

  if (!page) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center px-4 text-center">
        <h1 className="text-2xl font-bold text-theme-text">{t('public.errors.notFoundCode')}</h1>
        <p className="mt-2 text-theme-text-muted">{t('public.errors.pageNotFound', { slug: slug ?? '' })}</p>
        <button
          type="button"
          onClick={() => navigate('/')}
          className={`mt-6 px-6 py-2.5 rounded-xl text-sm font-bold ${BTN_PRIMARY}`}
        >
          {t('public.nav.home')}
        </button>
      </div>
    );
  }

  return <PageRenderer page={page} />;
}

export const PublicSiteLayout: React.FC = () => {
  const { t } = useI18n();
  useAnalyticsPageview();
  const [searchOpen, setSearchOpen] = useState(false);
  const { pathname } = useLocation();
  const navigate = useNavigate();
  const { user, pendingTwoFactor } = useAuth();
  const { getPageBySlug, getArticleBySlug, navigation } = usePublicSite();
  const { settings } = useSettingsContext();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');
  const navLayout = useMemo(() => resolveNavigationLayout(settings), [settings]);
  const showTopNav = navLayout.placement === 'top' || navLayout.placement === 'both';
  const showSideNav = navLayout.placement === 'side' || navLayout.placement === 'both';

  const showCmsBar = Boolean(user && !pendingTwoFactor);

  const currentDoc: CMSBarDoc | undefined = useMemo(() => {
    if (pathname.startsWith('/blog/')) {
      const articleSlug = pathname.split('/')[2];
      const article = getArticleBySlug(articleSlug);
      if (article) {
        return { type: 'article', slug: article.slug, title: article.title };
      }
      if (articleSlug) {
        return { type: 'article', slug: articleSlug, title: articleSlug };
      }
    }
    if (pathname === '/') {
      const home = getPageBySlug('home') ?? getPageBySlug('index');
      if (home) {
        return { type: 'page', slug: home.slug, title: home.title };
      }
    } else if (pathname !== '/blog' && !ADMIN_PREFIXES.some((p) => pathname.startsWith(p))) {
      const slug = pathname.slice(1);
      const page = getPageBySlug(slug);
      if (page) {
        return { type: 'page', slug: page.slug, title: page.title };
      }
    }
    return undefined;
  }, [pathname, getPageBySlug, getArticleBySlug]);

  const seoType = currentDoc?.type ?? null;
  const seoSlug = currentDoc?.slug ?? null;
  useSeoMeta(seoType, seoSlug);

  React.useEffect(() => {
    const feeds = settings?.feeds as { enabled?: boolean } | undefined;
    if (feeds?.enabled === false) {
      return;
    }

    const origin = window.location.origin;

    const rssHref = `${origin}/feed.xml`;
    let rssLink = document.querySelector<HTMLLinkElement>('link[data-paginium-feed="rss"]');
    if (!rssLink) {
      rssLink = document.createElement('link');
      rssLink.rel = 'alternate';
      rssLink.type = 'application/rss+xml';
      rssLink.dataset.paginiumFeed = 'rss';
      document.head.appendChild(rssLink);
    }
    rssLink.href = rssHref;
    rssLink.title = t('public.meta.rssTitle', { siteName });

    const sitemapHref = `${origin}/sitemap.xml`;
    let sitemapLink = document.querySelector<HTMLLinkElement>('link[data-paginium-feed="sitemap"]');
    if (!sitemapLink) {
      sitemapLink = document.createElement('link');
      sitemapLink.rel = 'sitemap';
      sitemapLink.type = 'application/xml';
      sitemapLink.dataset.paginiumFeed = 'sitemap';
      document.head.appendChild(sitemapLink);
    }
    sitemapLink.href = sitemapHref;
    sitemapLink.title = t('public.meta.sitemapTitle', { siteName });
  }, [settings?.feeds, siteName, t]);

  return (
    <MaintenanceGate>
      <CookieConsentProvider>
      <div className="min-h-screen flex flex-col bg-theme-surface text-theme-text transition-colors">
      <DemoPublicStrip />
      {showCmsBar && <CMSBar currentDoc={currentDoc} />}
      <Navbar
        onOpenSearch={() => setSearchOpen(true)}
        showPrimaryNav={showTopNav}
        navLayout={navLayout}
      />
      <div className="flex-1 flex min-h-0">
        {showSideNav ? (
          <div className={`pg-public-side-column ${sideNavBreakpointClass(navLayout.sideBreakpoint)}`}>
            <SideNav items={navigation} layout={navLayout} className="pg-public-side-nav-sticky" />
          </div>
        ) : null}
        <div className="flex-1 min-w-0">
          <Outlet />
        </div>
      </div>
      <Footer />
      <BackToTopButton />
      <CookieConsentBanner />
      <SiteSearchModal
        isOpen={searchOpen}
        onClose={() => setSearchOpen(false)}
        onSelectRoute={(path) => navigate(path)}
      />
      </div>
      </CookieConsentProvider>
    </MaintenanceGate>
  );
};

export default PublicSiteLayout;
