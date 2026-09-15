// frontend/src/components/layout/ResponsiveLayout.tsx
import React, { useState, useEffect, useRef } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { useMediaQuery } from '../../hooks/useMediaQuery';
import { useAuth } from '../../hooks/useAuth';
import { ChangePasswordModal } from '../auth/ChangePasswordModal';
import { AdminSidebar } from '../backend/AdminSidebar';
import { AdminHeader } from '../backend/AdminHeader';
import { AdminCommandPalette } from '../backend/AdminCommandPalette';
import { DemoModeBanner } from '../backend/DemoModeBanner';
import { OnboardingTour } from '../backend/OnboardingTour';
import { BackToTopButton } from '../frontend/BackToTopButton';
import { useOpenLinksInNewTab } from '../../hooks/useOpenLinksInNewTab';
import { openExternalUrl } from '../../utils/linkTarget';
import { useI18n } from '../../context/I18nContext';
import { useTheme } from '../../context/ThemeContext';
import { useSettings } from '../../hooks/useSettings';
import { AdminTopNav } from '../backend/AdminTopNav';
import { adminChromeCssVars, resolveAdminChrome } from '../../theme/adminChrome';

interface ResponsiveLayoutProps {
  children: React.ReactNode;
}

const SIDEBAR_COLLAPSED_KEY = 'paginium.admin.sidebarCollapsed';

export const ResponsiveLayout: React.FC<ResponsiveLayoutProps> = ({ children }) => {
  const { t } = useI18n();
  const { isDark } = useTheme();
  const { settings } = useSettings();
  const chrome = resolveAdminChrome(settings.ui);
  const useTopNav = chrome.navPlacement === 'top';
  const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
    if (typeof window === 'undefined') {
      return false;
    }
    return window.localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1';
  });
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [changePasswordOpen, setChangePasswordOpen] = useState(false);
  const [commandPaletteOpen, setCommandPaletteOpen] = useState(false);
  const location = useLocation();
  const scrollContainerRef = useRef<HTMLDivElement>(null);
  const isMobile = useMediaQuery('(max-width: 1023px)');
  const { twoFactorSetupPending } = useAuth();
  const openInNewTab = useOpenLinksInNewTab();

  useEffect(() => {
    window.localStorage.setItem(SIDEBAR_COLLAPSED_KEY, sidebarCollapsed ? '1' : '0');
  }, [sidebarCollapsed]);

  useEffect(() => {
    setMobileMenuOpen(false);
    scrollContainerRef.current?.scrollTo({ top: 0, behavior: 'auto' });
  }, [location.pathname]);

  useEffect(() => {
    if (!isMobile) {
      setMobileMenuOpen(false);
    }
  }, [isMobile]);

  useEffect(() => {
    const onKeyDown = (event: KeyboardEvent) => {
      const key = event.key.toLowerCase();
      if (!(event.ctrlKey || event.metaKey) || key !== 'k') {
        return;
      }

      const target = event.target;
      if (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        (target instanceof HTMLElement && target.isContentEditable)
      ) {
        return;
      }

      event.preventDefault();
      setCommandPaletteOpen((open) => !open);
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  const publicSiteUrl =
    import.meta.env.VITE_PUBLIC_URL ||
    (typeof window !== 'undefined' ? window.location.origin : '/');

  return (
    <div
      data-testid="admin-shell"
      data-admin-nav={chrome.navPlacement}
      className={`admin-shell flex h-screen bg-admin-canvas font-sans overflow-hidden selection:bg-admin-primary selection:text-white transition-colors ${isDark ? 'dark' : ''}`}
      style={adminChromeCssVars(chrome) as React.CSSProperties}
    >
      {!useTopNav && (
        <AdminSidebar
          collapsed={sidebarCollapsed}
          onToggleCollapse={() => setSidebarCollapsed((value) => !value)}
          mobileOpen={mobileMenuOpen}
          onMobileClose={() => setMobileMenuOpen(false)}
        />
      )}

      {mobileMenuOpen && !useTopNav && (
        <button
          type="button"
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          aria-label={t('admin.header.closeMenu')}
          onClick={() => setMobileMenuOpen(false)}
        />
      )}

      <div className="flex-1 flex flex-col min-w-0 min-h-0 overflow-visible">
        <div className="relative z-50 shrink-0 overflow-visible">
          <AdminHeader
            onGoToWebsite={() => openExternalUrl(publicSiteUrl, openInNewTab)}
            onOpenMobileMenu={() => setMobileMenuOpen((open) => !open)}
            onOpenChangePassword={() => setChangePasswordOpen(true)}
            onOpenCommandPalette={() => setCommandPaletteOpen(true)}
            sidebarCollapsed={sidebarCollapsed}
            onToggleSidebar={() => setSidebarCollapsed((value) => !value)}
            navPlacement={chrome.navPlacement}
          />
          {useTopNav && (
            <AdminTopNav mobileOpen={mobileMenuOpen} onNavigate={() => setMobileMenuOpen(false)} />
          )}
        </div>
        <div
          ref={scrollContainerRef}
          data-testid="admin-scroll-pane"
          className="relative z-0 flex-1 min-h-0 overflow-y-auto"
        >
          <DemoModeBanner />
          <OnboardingTour />
          {twoFactorSetupPending && location.pathname !== '/account/security' && (
            <div className="mx-6 sm:mx-8 mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
              {t('admin.header.twoFactorPending')}{' '}
              <Link to="/account/security" className="font-semibold underline">
                {t('admin.header.twoFactorLink')}
              </Link>
              .
            </div>
          )}
          <main className="p-4 sm:p-6 max-w-[90rem] mx-auto w-full flex-1 animate-fadeIn">
            {children}
          </main>
          <BackToTopButton scrollContainerRef={scrollContainerRef} variant="admin" />
        </div>
      </div>

      <ChangePasswordModal open={changePasswordOpen} onClose={() => setChangePasswordOpen(false)} />
      <AdminCommandPalette isOpen={commandPaletteOpen} onClose={() => setCommandPaletteOpen(false)} />
    </div>
  );
};

export default ResponsiveLayout;
