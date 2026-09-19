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
import { SupportChatPresenceBubble } from '../backend/SupportChatPresenceBubble';
import { useAnalyticsPageview } from '../../hooks/useAnalyticsPageview';
import { galleryPublicSlug } from '../../utils/galleryPublicRoute';
import { BTN_PRIMARY, PUBLIC_SPINNER } from '../../theme/publicUiClasses';
import { resolveNavigationLayout } from '../../utils/navigationLayoutSettings';
import { resolvePublicNavChrome } from '../../utils/publicNavChrome';
import { resolveThemeShell } from '../../theme/themeShellRegistry';
import { ThemeShellBoundary } from './ThemeShellBoundary';
import { ThemeScriptLoader } from '../frontend/ThemeScriptLoader';
import { PublicHeaderStack } from './PublicHeaderStack';
import { isAdminAppRoute } from '../../utils/appRoutes';

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
  const { getPageBySlug, getArticleBySlug, navigation, secondaryNavigation } = usePublicSite();
  const { settings } = useSettingsContext();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');
  const activeThemeId = settings?.appearance?.activeThemeId ?? 'paginium-core';
  const [shellFailed, setShellFailed] = React.useState(false);
  React.useEffect(() => {
    setShellFailed(false);
  }, [activeThemeId]);
  const ThemeShell = !shellFailed ? resolveThemeShell(activeThemeId) : null;
  const navLayout = useMemo(() => resolveNavigationLayout(settings), [settings]);
  const chrome = useMemo(
    () => resolvePublicNavChrome(settings, secondaryNavigation.length),
    [settings, secondaryNavigation.length]
  );
  const showTopNav = chrome.showTopPrimary;
  const showSideColumn = chrome.showSidePrimary || chrome.showSideSecondary;
  const sideOnRight = chrome.showSideSecondary && chrome.secondary.side === 'right';
  const sideSticky = chrome.showSideSecondary
    ? chrome.secondary.position === 'sticky'
    : true;

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
    } else if (pathname !== '/blog' && !isAdminAppRoute(pathname)) {
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

  const navUi = {
    defaultPreviewScale: Number(settings.navigationUi?.defaultPreviewScale ?? 1.5),
    maxTooltipWidthPx: Number(settings.navigationUi?.maxTooltipWidthPx ?? 280),
    enableHoverAnimations: settings.navigationUi?.enableHoverAnimations !== false,
  };

  const sideItems = chrome.showSideSecondary ? secondaryNavigation : navigation;
  const sideColumn = showSideColumn ? (
    <div
      className={`pg-public-side-column ${sideOnRight ? 'pg-public-side-column-right' : ''} ${chrome.sideColumnClass}`}
    >
      <SideNav
        items={sideItems}
        layout={navLayout}
        className={sideSticky ? 'pg-public-side-nav-sticky' : 'pg-public-side-nav-scroll'}
        accordion={chrome.showSideSecondary && chrome.secondary.position === 'sticky'}
        hoverPreview={chrome.showSideSecondary}
        previewSide={sideOnRight ? 'start' : 'end'}
        navUi={navUi}
        ariaLabel={
          chrome.showSideSecondary ? t('public.nav.secondaryMenu') : t('public.nav.sideMenu')
        }
      />
    </div>
  ) : null;

  const mainColumn = (
    <div className={`flex-1 flex min-h-0 min-w-0 ${sideOnRight ? 'flex-row-reverse' : ''}`}>
      {sideColumn}
      <div className="flex-1 min-w-0 pg-public-content-well">
        <Outlet />
      </div>
    </div>
  );

  const headerStack = (
    <PublicHeaderStack>
      {showCmsBar && <CMSBar currentDoc={currentDoc} />}
      <Navbar
        onOpenSearch={() => setSearchOpen(true)}
        showPrimaryNav={showTopNav}
        navLayout={navLayout}
        chrome={chrome}
        secondaryItems={secondaryNavigation}
        wideHeader={showSideColumn}
      />
    </PublicHeaderStack>
  );

  const coreChrome = (
    <>
      {headerStack}
      {mainColumn}
      <Footer />
    </>
  );

  return (
    <MaintenanceGate>
      <CookieConsentProvider>
      <div
        className={`min-h-screen flex flex-col bg-theme-surface text-theme-text transition-colors ${
          showCmsBar ? 'has-cms-bar' : ''
        } ${showSideColumn ? 'has-public-side-nav' : ''}`}
        data-active-theme={activeThemeId}
        data-public-chrome={
          chrome.showSideSecondary ? 'top+catalog' : chrome.showSidePrimary ? 'side' : 'top'
        }
      >
      <DemoPublicStrip />
      <ThemeScriptLoader />
      {ThemeShell ? (
        <ThemeShellBoundary
          themeId={activeThemeId}
          fallback={coreChrome}
          onShellError={() => setShellFailed(true)}
        >
          <ThemeShell
            siteName={siteName}
            onOpenSearch={() => setSearchOpen(true)}
            showPrimaryNav={showTopNav}
            navLayout={navLayout}
            chrome={chrome}
            secondaryItems={secondaryNavigation}
            wideHeader={showSideColumn}
            headerPrefix={showCmsBar ? <CMSBar currentDoc={currentDoc} /> : null}
          >
            {mainColumn}
          </ThemeShell>
        </ThemeShellBoundary>
      ) : (
        coreChrome
      )}
      <SupportChatPresenceBubble variant="public" />
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
