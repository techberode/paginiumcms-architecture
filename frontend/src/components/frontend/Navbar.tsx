// frontend/src/components/frontend/Navbar.tsx
import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { ChevronDown, Search, Shield, Menu, X } from 'lucide-react';
import { usePublicSite, type PublicNavItem } from '../../context/PublicSiteContext';
import { useSettingsContext } from '../../context/SettingsContext';
import { useAuth } from '../../hooks/useAuth';
import { useI18n } from '../../context/I18nContext';
import { SiteLogo } from '../branding/SiteLogo';
import { PublicThemeToggle } from './PublicThemeToggle';
import { usePublicAppearanceContext } from '../../context/PublicAppearanceProvider';
import { NavDropdownEntry, NavMenuVisual } from './NavMenuVisual';
import { navigationItemHasVisual } from '../../utils/navigationRich';
import { BTN_PRIMARY_GRADIENT, LOGO_FALLBACK, NAV_LINK_ACTIVE, NAV_LINK_IDLE } from '../../theme/publicUiClasses';

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
        <span className={`block font-normal text-theme-text-muted ${descriptionClassName}`}>
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
      active ? NAV_LINK_ACTIVE : NAV_LINK_IDLE
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
          active ? NAV_LINK_ACTIVE : NAV_LINK_IDLE
        }`}
        onClick={() => onNavigate(item.path)}
      >
        <NavItemContent item={item} />
        <ChevronDown className="w-4 h-4 shrink-0 mt-0.5" />
      </button>
      {open ? (
        <div className="absolute left-0 top-full pt-2 min-w-[240px] z-50">
          <div className="rounded-xl border border-theme-border bg-theme-surface-elevated shadow-xl py-2">
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
            isPathActive(item.path) ? NAV_LINK_ACTIVE : `${NAV_LINK_IDLE} text-theme-text`
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

  const { allowUserToggle, resolvedTheme, toggleVisitorTheme } = usePublicAppearanceContext();

  const handleNavigate = (path: string) => {
    navigate(path);
    setMobileMenuOpen(false);
  };

  return (
    <header className="backdrop-blur-md border-b border-theme-border bg-theme-surface-elevated/90 sticky top-0 z-40 transition-colors">
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
            fallbackClassName={`w-10 h-10 ${LOGO_FALLBACK} group-hover:scale-105 transition-transform`}
            nameClassName="text-lg font-black tracking-tight text-theme-text truncate"
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
          {allowUserToggle ? (
            <PublicThemeToggle resolvedTheme={resolvedTheme} onToggle={toggleVisitorTheme} />
          ) : null}

          <button
            type="button"
            onClick={onOpenSearch}
            className="flex items-center gap-2 bg-theme-surface hover:opacity-80 text-theme-text px-3.5 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer border border-theme-border/50"
          >
            <Search className="w-4 h-4 text-theme-primary" />
            <span className="hidden sm:inline text-theme-text-muted font-normal">{t('public.nav.search')}</span>
          </button>

          {user ? (
            <button
              type="button"
              onClick={() => navigate('/dashboard')}
              className={`flex items-center gap-2 px-4 py-2 text-xs ${BTN_PRIMARY_GRADIENT} cursor-pointer`}
            >
              <Shield className="w-4 h-4" />
              <span className="hidden sm:inline">{t('public.nav.adminButton')}</span>
            </button>
          ) : (
            <button
              type="button"
              onClick={() => navigate('/login')}
              className="flex items-center gap-1.5 bg-theme-text hover:opacity-90 text-theme-surface px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer"
            >
              <Shield className="w-3.5 h-3.5" />
              <span className="hidden sm:inline">{t('public.nav.loginButton')}</span>
            </button>
          )}

          <button
            type="button"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="md:hidden p-2 text-theme-text hover:bg-theme-surface rounded-xl transition-colors"
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {mobileMenuOpen ? (
        <div className="md:hidden border-t border-theme-border bg-theme-surface-elevated px-4 py-6 shadow-xl animate-fadeIn">
          <div className="flex flex-col gap-2">
            <MobileNavItems items={sortedNav} isPathActive={isPathActive} onNavigate={handleNavigate} />
          </div>
        </div>
      ) : null}
    </header>
  );
};

export default Navbar;
