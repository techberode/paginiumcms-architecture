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
import { useOpenLinksInNewTab } from '../../hooks/useOpenLinksInNewTab';
import { openExternalUrl } from '../../utils/linkTarget';

interface ResponsiveLayoutProps {
  children: React.ReactNode;
}

const SIDEBAR_COLLAPSED_KEY = 'paginium.admin.sidebarCollapsed';

export const ResponsiveLayout: React.FC<ResponsiveLayoutProps> = ({ children }) => {
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
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setCommandPaletteOpen(true);
      }
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, []);

  const publicSiteUrl =
    import.meta.env.VITE_PUBLIC_URL ||
    (typeof window !== 'undefined' ? window.location.origin : '/');

  return (
    <div className="flex h-screen bg-slate-50 dark:bg-slate-950 font-sans overflow-hidden selection:bg-indigo-500 selection:text-white transition-colors">
      <AdminSidebar
        collapsed={sidebarCollapsed}
        onToggleCollapse={() => setSidebarCollapsed((value) => !value)}
        mobileOpen={mobileMenuOpen}
        onMobileClose={() => setMobileMenuOpen(false)}
      />

      {mobileMenuOpen && (
        <button
          type="button"
          className="fixed inset-0 z-40 bg-black/50 lg:hidden"
          aria-label="Close menu"
          onClick={() => setMobileMenuOpen(false)}
        />
      )}

      <div ref={scrollContainerRef} className="flex-1 flex flex-col min-w-0 overflow-y-auto">
        <AdminHeader
          onGoToWebsite={() => openExternalUrl(publicSiteUrl, openInNewTab)}
          onOpenMobileMenu={() => setMobileMenuOpen(true)}
          onOpenChangePassword={() => setChangePasswordOpen(true)}
          sidebarCollapsed={sidebarCollapsed}
          onToggleSidebar={() => setSidebarCollapsed((value) => !value)}
        />
        <DemoModeBanner />
        {twoFactorSetupPending && location.pathname !== '/account/security' && (
          <div className="mx-6 sm:mx-8 mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
            Dokončite nastavenie 2FA — naskenujte QR kód a zadajte overovací kód v{' '}
            <Link to="/account/security" className="font-semibold underline">
              bezpečnosti účtu
            </Link>
            .
          </div>
        )}
        <main className="p-6 sm:p-8 max-w-7xl mx-auto w-full flex-1 animate-fadeIn">
          {children}
        </main>
      </div>

      <ChangePasswordModal open={changePasswordOpen} onClose={() => setChangePasswordOpen(false)} />
      <AdminCommandPalette isOpen={commandPaletteOpen} onClose={() => setCommandPaletteOpen(false)} />
    </div>
  );
};

export default ResponsiveLayout;
