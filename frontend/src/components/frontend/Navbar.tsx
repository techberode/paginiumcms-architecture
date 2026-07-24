// frontend/src/components/frontend/Navbar.tsx
import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { ChevronDown, Search, Shield, Menu, X } from 'lucide-react';
import { usePublicSite, type PublicNavItem } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { SiteLogo } from '../branding/SiteLogo';
import { NavDropdownEntry, NavMenuVisual } from './NavMenuVisual';
import { navigationItemHasVisual } from '../../utils/navigationRich';

interface NavbarProps {
  onOpenSearch: () => void;
  previewMode?: boolean;
}

const NavItemContent: React.FC<{
  item: PublicNavItem;
  labelClassName?: string;
  descriptionClassName?: string;
}> = ({ item, labelClassName = 'text-sm font-semibold', descriptionClassName = 'text-xs' }) => (
  <>
    {navigationItemHasVisual(item.iconType, item.iconValue) ? <NavMenuVisual item={item} /> : null}
    <span className="min-w-0 text-left">
      <span className={`block ${labelClassName}`}>{item.label}</span>
      {item.description ? (
        <span className={`block font-normal text-slate-500 dark:text-slate-400 ${descriptionClassName}`}>
          {item.description}
        </span>
      ) : null}
    </span>
  </>
);

const NavLinkButton: React.FC<{
  item: PublicNavItem;
  active: boolean;
  onNavigate: (path: string) => void;
  className?: string;
}> = ({ item, active, onNavigate, className = '' }) => (
  <button
    type="button"
    onClick={() => onNavigate(item.path)}
    className={`inline-flex items-start gap-2 px-4 py-2 rounded-xl transition-all cursor-pointer ${className} ${
      active
        ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50'
        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
    }`}
  >
    <NavItemContent item={item} />
  </button>
);

const DesktopNavItem: React.FC<{
  item: PublicNavItem;
  isPathActive: (path: string) => boolean;
  onNavigate: (path: string) => void;
  navUi: {
    defaultPreviewScale: number;
    maxTooltipWidthPx: number;
    enableHoverAnimations: boolean;
  };
}> = ({ item, isPathActive, onNavigate, navUi }) => {
  const [open, setOpen] = useState(false);
  const hasChildren = (item.children?.length ?? 0) > 0;
  const active =
    isPathActive(item.path) ||
    (item.children?.some((child) => isPathActive(child.path)) ?? false);

  if (!hasChildren) {
    return <NavLinkButton item={item} active={active} onNavigate={onNavigate} />;
  }

  return (
    <div className="relative" onMouseEnter={() => setOpen(true)} onMouseLeave={() => setOpen(false)}>
      <button
        type="button"
        className={`inline-flex items-start gap-2 px-4 py-2 rounded-xl transition-all ${
          active
            ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50'
            : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'
        }`}
        onClick={() => onNavigate(item.path)}
      >
        <NavItemContent item={item} />
        <ChevronDown className="w-4 h-4 shrink-0 mt-0.5" />
      </button>
      {open ? (
        <div className="absolute left-0 top-full pt-2 min-w-[240px] z-50">
          <div className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl py-2">
            {item.children?.map((child) => (
              <div key={child.id}>
                <NavDropdownEntry
                  item={child}
                  onNavigate={onNavigate}
                  isActive={isPathActive(child.path)}
                  navUi={navUi}
                />
                {child.children?.map((grand) => (
                  <NavDropdownEntry
                    key={grand.id}
                    item={grand}
                    onNavigate={onNavigate}
                    isActive={isPathActive(grand.path)}
                    compact
                    navUi={navUi}
                  />
                ))}
              </div>
            ))}
          </div>
        </div>
      ) : null}
    </div>
  );
};

const MobileNavItems: React.FC<{
  items: PublicNavItem[];
  depth?: number;
  isPathActive: (path: string) => boolean;
  onNavigate: (path: string) => void;
}> = ({ items, depth = 0, isPathActive, onNavigate }) => (
  <>
    {items.map((item) => (
      <div key={item.id} style={{ marginLeft: `${depth * 0.75}rem` }}>
        <button
          type="button"
          onClick={() => onNavigate(item.path)}
          className={`w-full p-3 rounded-xl text-left flex items-start gap-3 ${
            isPathActive(item.path)
              ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'
              : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'
          }`}
        >
          <NavItemContent item={item} labelClassName="text-base font-bold" descriptionClassName="text-sm" />
        </button>
        {item.children && item.children.length > 0 ? (
          <MobileNavItems
            items={item.children}
            depth={depth + 1}
            isPathActive={isPathActive}
            onNavigate={onNavigate}
          />
        ) : null}
      </div>
    ))}
  </>
);

export const Navbar: React.FC<NavbarProps> = ({ onOpenSearch, previewMode = false }) => {
  const { navigation } = usePublicSite();
  const { get } = useSettingsContext();
  const { user } = useAuth();
  const { t } = useI18n();
  const location = useLocation();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const isPathActive = (navPath: string) => {
    if (navPath === '/') {
      return location.pathname === '/';
    }
    return location.pathname.startsWith(navPath);
  };

  const sortedNav = [...navigation].sort((a, b) => a.order - b.order);

  const navUi = {
    defaultPreviewScale: Number(get('navigationUi.defaultPreviewScale', 1.5)),
    maxTooltipWidthPx: Number(get('navigationUi.maxTooltipWidthPx', 280)),
    enableHoverAnimations: Boolean(get('navigationUi.enableHoverAnimations', true)),
  };

  const handleNavigate = (path: string) => {
    navigate(path);
    setMobileMenuOpen(false);
  };

  return (
    <header className="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 sticky top-0 z-40 transition-colors">
      {previewMode && (
        <div className="bg-amber-500 text-amber-950 text-center text-[11px] font-bold py-1">
          {t('public.nav.previewBanner')}
        </div>
      )}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <button
          type="button"
          onClick={() => navigate('/')}
          className="flex items-center gap-3 cursor-pointer group"
        >
          <SiteLogo
            showName
            className="flex items-center gap-3"
            imageClassName="h-10 w-auto max-w-[160px] object-contain group-hover:scale-105 transition-transform"
            fallbackClassName="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-transform shrink-0"
            nameClassName="text-lg font-black tracking-tight text-slate-900 dark:text-white truncate"
          />
        </button>

        <nav className="hidden md:flex items-center gap-1">
          {sortedNav.map((item) => (
            <DesktopNavItem
              key={item.id}
              item={item}
              isPathActive={isPathActive}
              onNavigate={handleNavigate}
              navUi={navUi}
            />
          ))}
        </nav>

        <div className="flex items-center gap-2 sm:gap-3">
          <button
            type="button"
            onClick={onOpenSearch}
            className="flex items-center gap-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
          >
            <Search className="w-4 h-4 text-indigo-500" />
            <span className="hidden sm:inline text-slate-500 dark:text-slate-400 font-normal">{t('public.nav.search')}</span>
          </button>

          {user ? (
            <button
              type="button"
              onClick={() => navigate('/dashboard')}
              className="flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-lg shadow-indigo-500/25 transition-all cursor-pointer"
            >
              <Shield className="w-4 h-4" />
              <span className="hidden sm:inline">{t('public.nav.adminButton')}</span>
            </button>
          ) : (
            <button
              type="button"
              onClick={() => navigate('/login')}
              className="flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer"
            >
              <Shield className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">{t('public.nav.loginButton')}</span>
            </button>
          )}

          <button
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="md:hidden p-2 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors"
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {mobileMenuOpen ? (
        <div className="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-6 shadow-xl animate-fadeIn">
          <div className="flex flex-col gap-2">
            <MobileNavItems items={sortedNav} isPathActive={isPathActive} onNavigate={handleNavigate} />
          </div>
        </div>
      ) : null}
    </header>
  );
};

export default Navbar;
