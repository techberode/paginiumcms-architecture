import React, { useEffect, useRef, useState } from 'react';
import { NavLink } from 'react-router-dom';
import { ChevronDown } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';
import { useAdminNavModel } from '../../hooks/useAdminNavModel';
import type { AdminNavItemDef } from '../../config/adminNavTypes';
import { ADMIN_NAV_ACTIVE, ADMIN_NAV_IDLE } from '../../theme/adminUiClasses';
import { AdminAccountMenu } from './AdminAccountMenu';
import { DeskNotificationBeacon } from './DeskNotificationBeacon';
import { TeamChatNotificationBeacon } from './TeamChatNotificationBeacon';

interface AdminTopNavProps {
  mobileOpen: boolean;
  onNavigate: () => void;
}

interface MenuAnchor {
  id: string;
  top: number;
  left: number;
}

export const AdminTopNav: React.FC<AdminTopNavProps> = ({ mobileOpen, onNavigate }) => {
  const { t } = useI18n();
  const { visibleSections, primaryItems, countFor, isItemActive } = useAdminNavModel();
  const [openId, setOpenId] = useState<string | null>(null);
  const [menuAnchor, setMenuAnchor] = useState<MenuAnchor | null>(null);
  const navRef = useRef<HTMLElement>(null);

  const closeMenu = () => {
    setOpenId(null);
    setMenuAnchor(null);
  };

  useEffect(() => {
    closeMenu();
  }, [mobileOpen]);

  useEffect(() => {
    const onDocClick = (event: MouseEvent) => {
      if (!navRef.current?.contains(event.target as Node)) {
        closeMenu();
      }
    };
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        closeMenu();
      }
    };
    const onReposition = () => closeMenu();
    document.addEventListener('mousedown', onDocClick);
    document.addEventListener('keydown', onKey);
    window.addEventListener('resize', onReposition);
    window.addEventListener('scroll', onReposition, true);
    return () => {
      document.removeEventListener('mousedown', onDocClick);
      document.removeEventListener('keydown', onKey);
      window.removeEventListener('resize', onReposition);
      window.removeEventListener('scroll', onReposition, true);
    };
  }, []);

  const linkClass = (active: boolean, inMenu: boolean) =>
    `flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors ${
      active ? ADMIN_NAV_ACTIVE : inMenu ? 'text-admin-text hover:bg-admin-canvas' : ADMIN_NAV_IDLE
    }`;

  const renderItem = (item: AdminNavItemDef, inMenu: boolean, onClick?: () => void) => {
    const Icon = item.icon;
    const label = t(item.labelKey);
    const count = countFor(item.id);
    const active = isItemActive(item.href);

    return (
      <NavLink
        key={item.id}
        to={item.href}
        onClick={() => {
          onClick?.();
          onNavigate();
        }}
        className={() => linkClass(active, inMenu)}
      >
        <Icon className="w-4 h-4 shrink-0" />
        <span className="flex-1 text-left line-clamp-1">{label}</span>
        {count !== undefined && (
          <span className="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-admin-canvas text-admin-muted">
            {count}
          </span>
        )}
      </NavLink>
    );
  };

  const openSectionMenu = (sectionId: string, button: HTMLButtonElement) => {
    if (openId === sectionId) {
      closeMenu();
      return;
    }
    const rect = button.getBoundingClientRect();
    setOpenId(sectionId);
    setMenuAnchor({
      id: sectionId,
      top: rect.bottom + 6,
      left: Math.max(8, Math.min(rect.left, window.innerWidth - 240)),
    });
  };

  const openSection = visibleSections.find((section) => section.id === openId);

  const desktopBar = (
    <div data-testid="admin-topnav-desktop" className="admin-topnav-bar hidden lg:flex items-center gap-1 px-4 sm:px-6 py-2 flex-wrap">
      <AdminAccountMenu variant="topnav" onNavigate={onNavigate} />
      <TeamChatNotificationBeacon variant="topnav" />
      <DeskNotificationBeacon variant="topnav" />
      {primaryItems.map((item) => renderItem(item, false))}
      {visibleSections.map((section) => {
        const sectionActive = section.items.some((item) => isItemActive(item.href));
        const expanded = openId === section.id;
        return (
          <div key={section.id} className="relative shrink-0">
            <button
              type="button"
              aria-expanded={expanded}
              aria-haspopup="menu"
              onClick={(event) => openSectionMenu(section.id, event.currentTarget)}
              className={`inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[13px] font-semibold transition-colors ${
                sectionActive || expanded
                  ? 'text-admin-sidebar-active-text bg-admin-sidebar-active'
                  : 'text-admin-sidebar-text hover:bg-admin-sidebar-hover'
              }`}
            >
              {t(section.labelKey)}
              <ChevronDown className={`w-3.5 h-3.5 transition-transform ${expanded ? 'rotate-180' : ''}`} />
            </button>
          </div>
        );
      })}
    </div>
  );

  const mobileAccordion = (
    <div
      data-testid="admin-topnav-mobile"
      className={`admin-topnav-bar ${mobileOpen ? 'block' : 'hidden'} lg:hidden border-t border-admin-border px-3 py-2 space-y-2 max-h-[70vh] overflow-y-auto`}
    >
      <div className="flex items-center gap-2">
        <AdminAccountMenu variant="topnav" onNavigate={onNavigate} />
        <TeamChatNotificationBeacon variant="topnav" />
        <DeskNotificationBeacon variant="topnav" />
      </div>
      {primaryItems.map((item) => renderItem(item, false))}
      {visibleSections.map((section) => {
        const expanded = openId === section.id;
        const sectionActive = section.items.some((item) => isItemActive(item.href));
        return (
          <div key={section.id}>
            <button
              type="button"
              aria-expanded={expanded}
              onClick={() => {
                setMenuAnchor(null);
                setOpenId(expanded ? null : section.id);
              }}
              className={`w-full flex items-center gap-2 px-3 py-2 rounded-lg text-[12px] font-bold uppercase tracking-wider ${
                sectionActive ? 'text-admin-sidebar-active-text' : 'text-admin-sidebar-muted'
              }`}
            >
              <ChevronDown className={`w-3.5 h-3.5 transition-transform ${expanded ? '' : '-rotate-90'}`} />
              <span className="flex-1 text-left">{t(section.labelKey)}</span>
            </button>
            {expanded && <div className="pl-2 space-y-0.5">{section.items.map((item) => renderItem(item, false))}</div>}
          </div>
        );
      })}
    </div>
  );

  return (
    <nav
      ref={navRef}
      data-testid="admin-topnav"
      className="admin-topnav relative shrink-0 z-50 overflow-visible"
      aria-label={t('admin.topnav.label')}
    >
      {desktopBar}
      {mobileAccordion}
      {openSection && menuAnchor && menuAnchor.id === openSection.id && (
        <div
          role="menu"
          data-testid="admin-topnav-menu"
          className="admin-topnav-menu fixed z-[80] min-w-[14rem] rounded-lg p-1.5"
          style={{ top: menuAnchor.top, left: menuAnchor.left }}
        >
          {openSection.items.map((item) => renderItem(item, true, closeMenu))}
        </div>
      )}
    </nav>
  );
};

export default AdminTopNav;
