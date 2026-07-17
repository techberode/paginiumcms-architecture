// frontend/src/components/layout/PublicSiteLayout.tsx
import React, { useMemo, useState } from 'react';
import { Outlet, useLocation, useNavigate, useParams } from 'react-router-dom';
import { Navbar } from '../frontend/Navbar';
import { Footer } from '../frontend/Footer';
import { CMSBar, CMSBarDoc } from '../frontend/CMSBar';
import { SiteSearchModal } from '../frontend/SiteSearchModal';
import { PageRenderer } from '../frontend/PageRenderer';
import { usePublicSite } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useAuth } from '../../hooks/useAuth';
import { useSeoMeta } from '../../hooks/useSeoMeta';

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
  const { getPageBySlug, loading } = usePublicSite();
  const home = getPageBySlug('home') ?? getPageBySlug('index');

  if (loading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (!home) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center px-4 text-center">
        <h1 className="text-3xl font-black text-slate-900 dark:text-white">PaginiumCMS</h1>
        <p className="mt-3 text-slate-500">Zatiaľ nie je publikovaná domovská stránka (slug: home).</p>
      </div>
    );
  }

  return <PageRenderer page={home} />;
}

export function PublicSlugPage() {
  const { slug } = useParams<{ slug: string }>();
  const { getPageBySlug, loading } = usePublicSite();
  const navigate = useNavigate();

  const page = slug ? getPageBySlug(slug) : undefined;

  if (loading) {
    return (
      <div className="min-h-[50vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600" />
      </div>
    );
  }

  if (!page) {
    return (
      <div className="min-h-[50vh] flex flex-col items-center justify-center px-4 text-center">
        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">404</h1>
        <p className="mt-2 text-slate-500">Stránka &quot;{slug}&quot; neexistuje.</p>
        <button
          type="button"
          onClick={() => navigate('/')}
          className="mt-6 bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold"
        >
          Domov
        </button>
      </div>
    );
  }

  return <PageRenderer page={page} />;
}

export const PublicSiteLayout: React.FC = () => {
  const [searchOpen, setSearchOpen] = useState(false);
  const { pathname } = useLocation();
  const navigate = useNavigate();
  const { user, pendingTwoFactor } = useAuth();
  const { getPageBySlug, getArticleBySlug } = usePublicSite();
  const { settings } = useSettingsContext();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');

  const showCmsBar = Boolean(user && !pendingTwoFactor);

  const currentDoc: CMSBarDoc | undefined = useMemo(() => {
    if (pathname.startsWith('/blog/')) {
      const articleSlug = pathname.split('/')[2];
      const article = getArticleBySlug(articleSlug);
      if (article) {
        return { type: 'article', slug: article.slug, title: article.title };
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

    const href = `${window.location.origin}/feed.xml`;
    let link = document.querySelector<HTMLLinkElement>('link[data-paginium-feed="rss"]');
    if (!link) {
      link = document.createElement('link');
      link.rel = 'alternate';
      link.type = 'application/rss+xml';
      link.dataset.paginiumFeed = 'rss';
      document.head.appendChild(link);
    }
    link.href = href;
    link.title = `${siteName} RSS`;
  }, [settings?.feeds, siteName]);

  return (
    <div className="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950 transition-colors">
      {showCmsBar && <CMSBar currentDoc={currentDoc} />}
      <Navbar onOpenSearch={() => setSearchOpen(true)} />
      <div className="flex-1">
        <Outlet />
      </div>
      <Footer />
      <SiteSearchModal
        isOpen={searchOpen}
        onClose={() => setSearchOpen(false)}
        onSelectRoute={(path) => navigate(path)}
      />
    </div>
  );
};

export default PublicSiteLayout;
