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
import { NavDropdownEntry } from './NavMenuVisual';
import { NavItemContent } from './navbarShared';
import { SideNav } from './SideNav';
import type { NavigationLayoutSettings } from '../../utils/navigationLayoutSettings';
import type { PublicNavChrome } from '../../utils/publicNavChrome';
import { BTN_PRIMARY_GRADIENT, LOGO_FALLBACK, NAV_LINK_ACTIVE, NAV_LINK_IDLE } from '../../theme/publicUiClasses';
import { useTextScale } from '../../hooks/useTextScale';
import { TextScaleControl } from '../common/TextScaleControl';

interface NavbarProps {
  onOpenSearch: () => void;
  previewMode?: boolean;
  showPrimaryNav?: boolean;
  navLayout?: NavigationLayoutSettings;
  chrome?: PublicNavChrome;
  secondaryItems?: PublicNavItem[];
  wideHeader?: boolean;
}

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

const DesktopDropdownTree: React.FC<{
  items: PublicNavItem[];
  depth: number;
  isPathActive: (path: string) => boolean;
  onNavigate: (path: string) => void;
  navUi: {
    defaultPreviewScale: number;
    maxTooltipWidthPx: number;
    enableHoverAnimations: boolean;
  };
}> = ({ items, depth, isPathActive, onNavigate, navUi }) => (
  <>
    {items.map((child) => (
      <div key={child.id}>
        <NavDropdownEntry
          item={child}
          onNavigate={onNavigate}
          isActive={isPathActive(child.path)}
          compact={depth > 0}
          navUi={navUi}
        />
        {child.children && child.children.length > 0 ? (
          <DesktopDropdownTree
            items={child.children}
            depth={depth + 1}
            isPathActive={isPathActive}
            onNavigate={onNavigate}
            navUi={navUi}
          />
        ) : null}
      </div>
    ))}
  </>
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
        <div className="absolute left-0 top-full pt-2 min-w-[240px] max-w-[min(18rem,calc(100vw-1.5rem))] z-50 pg-public-top-dropdown">
          <div className="rounded-xl border border-theme-border bg-theme-surface-elevated shadow-xl py-2">
            <DesktopDropdownTree
              items={item.children ?? []}
              depth={0}
              isPathActive={isPathActive}
              onNavigate={onNavigate}
              navUi={navUi}
            />
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

export const Navbar: React.FC<NavbarProps> = ({
  onOpenSearch,
  previewMode = false,
  showPrimaryNav = true,
  navLayout,
  chrome,
  secondaryItems = [],
  wideHeader = false,
}) => {
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
  const publicTextScaleEnabled = get('appearance.publicTextScaleControlEnabled', true) !== false;
  const textScale = useTextScale('public', publicTextScaleEnabled);

  const handleNavigate = (path: string) => {
    navigate(path);
    setMobileMenuOpen(false);
  };

  const showDesktopPrimary = Boolean(showPrimaryNav && (chrome ? chrome.showTopPrimary : true));
  const hamburgerClass = chrome?.hamburgerClass ?? 'md:hidden';
  const desktopNavClass = chrome?.topNavClass ?? 'hidden md:flex';
  const headerInnerClass = wideHeader
    ? 'w-full px-3 sm:px-6 lg:px-8 min-h-16 py-2 flex items-center justify-between gap-2 sm:gap-3 min-w-0'
    : 'max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 min-h-16 py-2 flex items-center justify-between gap-2 sm:gap-3 min-w-0';

  return (
    <header className="pg-public-header backdrop-blur-md border-b border-theme-border bg-theme-surface-elevated/90 transition-colors">
      {previewMode && (
        <div className="bg-amber-500 text-amber-950 text-center text-[11px] font-bold py-1">
          {t('public.nav.previewBanner')}
        </div>
      )}
      <div className={headerInnerClass}>
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

        {showDesktopPrimary ? (
          <nav className={`${desktopNavClass} items-center gap-1 flex-wrap overflow-visible pg-public-top-nav`}>
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
        ) : (
          <div className="flex-1" />
        )}

        <div className="flex items-center gap-2 sm:gap-3">
          {allowUserToggle ? (
            <PublicThemeToggle resolvedTheme={resolvedTheme} onToggle={toggleVisitorTheme} />
          ) : null}

          {publicTextScaleEnabled ? (
            <TextScaleControl
              variant="public"
              active={textScale.active}
              percent={textScale.percent}
              onIncrease={textScale.increase}
              onDecrease={textScale.decrease}
              onToggle={textScale.toggle}
            />
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
            className={`${hamburgerClass} p-2 text-theme-text hover:bg-theme-surface rounded-xl transition-colors shrink-0`}
            aria-expanded={mobileMenuOpen}
            aria-controls="pg-public-mobile-nav"
            aria-label={mobileMenuOpen ? t('public.nav.closeMenu') : t('public.nav.openMenu')}
          >
            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
          </button>
        </div>
      </div>

      {mobileMenuOpen ? (
        <div
          id="pg-public-mobile-nav"
          className={`${hamburgerClass} border-t border-theme-border bg-theme-surface-elevated px-4 py-6 shadow-xl animate-fadeIn max-h-[min(70vh,32rem)] overflow-y-auto`}
        >
          <div className="flex flex-col gap-6">
            {showPrimaryNav || chrome?.showSidePrimary ? (
              <section aria-label={t('public.nav.primaryMenu')}>
                <p className="text-[11px] font-bold uppercase tracking-wider text-theme-text-muted mb-2">
                  {t('public.nav.primaryMenu')}
                </p>
                {navLayout && (chrome?.showSidePrimary || navLayout.placement === 'side') ? (
                  <SideNav items={sortedNav} layout={navLayout} onNavigate={handleNavigate} />
                ) : (
                  <div className="flex flex-col gap-2">
                    <MobileNavItems items={sortedNav} isPathActive={isPathActive} onNavigate={handleNavigate} />
                  </div>
                )}
              </section>
            ) : null}
            {chrome?.showSideSecondary && secondaryItems.length > 0 && navLayout ? (
              <section aria-label={t('public.nav.secondaryMenu')}>
                <p className="text-[11px] font-bold uppercase tracking-wider text-theme-text-muted mb-2">
                  {t('public.nav.secondaryMenu')}
                </p>
                <SideNav
                  items={secondaryItems}
                  layout={navLayout}
                  onNavigate={handleNavigate}
                  accordion={chrome.secondary.position === 'sticky'}
                  ariaLabel={t('public.nav.secondaryMenu')}
                />
              </section>
            ) : null}
          </div>
        </div>
      ) : null}
    </header>
  );
};

export default Navbar;
