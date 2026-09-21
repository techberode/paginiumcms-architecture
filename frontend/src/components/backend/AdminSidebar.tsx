// frontend/src/components/backend/AdminSidebar.tsx
import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { ChevronDown, ChevronLeft, ChevronRight, Database, Shield } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useSettings } from '../../hooks/useSettings';
import { useI18n } from '../../context/I18nContext';
import { useAdminNavModel } from '../../hooks/useAdminNavModel';
import type { AdminNavItemDef } from '../../config/adminNavTypes';
import { SiteLogo } from '../branding/SiteLogo';
import { DeskNotificationBeacon } from './DeskNotificationBeacon';
import { TeamChatNotificationBeacon } from './TeamChatNotificationBeacon';
import { ADMIN_NAV_ACTIVE, ADMIN_NAV_IDLE } from '../../theme/adminUiClasses';

interface AdminSidebarProps {
  collapsed: boolean;
  onToggleCollapse: () => void;
  mobileOpen?: boolean;
  onMobileClose?: () => void;
}

export const AdminSidebar: React.FC<AdminSidebarProps> = ({
  collapsed,
  onToggleCollapse,
  mobileOpen = false,
  onMobileClose,
}) => {
  const { t } = useI18n();
  const { user } = useAuth();
  const navigate = useNavigate();
  const { settings } = useSettings();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');
  const { visibleSections, primaryItems, openSections, setOpenSections, countFor, isItemActive } =
    useAdminNavModel();

  const displayName = user?.name || t('admin.sidebar.userFallback');
  const roleKey = (user?.roles?.[0] ?? 'editor').toLowerCase();
  const roleLabel =
    t(`admin.roles.${roleKey}`) !== `admin.roles.${roleKey}` ? t(`admin.roles.${roleKey}`) : roleKey;

  const linkClass = (isActive: boolean) =>
    `w-full flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors cursor-pointer group relative ${
      isActive ? ADMIN_NAV_ACTIVE : ADMIN_NAV_IDLE
    }`;

  const renderItem = (item: AdminNavItemDef) => {
    const Icon = item.icon;
    const label = t(item.labelKey);
    const count = countFor(item.id);
    const active = isItemActive(item.href);

    return (
      <NavLink
        key={item.id}
        to={item.href}
        title={collapsed ? label : undefined}
        onClick={onMobileClose}
        className={() => linkClass(active)}
      >
        <Icon className="w-4 h-4 shrink-0" />
        {!collapsed && <span className="flex-1 text-left line-clamp-1">{label}</span>}
        {!collapsed && count !== undefined && (
          <span
            className={`admin-nav-count px-1.5 py-0.5 rounded-md text-[10px] font-bold ml-auto ${
              active ? 'admin-nav-count-on' : ''
            }`}
          >
            {count}
          </span>
        )}
      </NavLink>
    );
  };

  return (
    <aside
      className={`admin-sidebar bg-admin-sidebar text-admin-sidebar-text border-r border-admin-border transition-all flex flex-col justify-between select-none shrink-0 z-50
        ${collapsed ? 'w-[4.5rem]' : 'w-64'}
        fixed inset-y-0 left-0 lg:static
        ${mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
      `}
    >
      <div className="min-h-0 flex flex-col">
        <div className="h-16 px-4 border-b border-admin-border flex items-center gap-3 overflow-hidden shrink-0">
          <SiteLogo
            showName={false}
            className="flex items-center gap-3 min-w-0"
            imageClassName="h-9 w-auto max-w-[120px] object-contain shrink-0"
            fallbackClassName="w-9 h-9 rounded-lg bg-admin-primary flex items-center justify-center text-white font-black text-lg shrink-0"
          />
          {!collapsed && (
            <div className="min-w-0">
              <span className="font-bold text-base text-admin-sidebar-text block leading-none truncate">
                {siteName}
              </span>
              <span className="text-[10px] font-semibold text-admin-primary uppercase tracking-wider block mt-1 truncate">
                {t('admin.sidebar.brandSubtitle')}
              </span>
            </div>
          )}
        </div>

        {user && !collapsed && (
          <div className="px-3 py-3 border-b border-admin-border shrink-0 flex items-center gap-2">
            <button
              type="button"
              data-testid="admin-sidebar-account"
              title={t('admin.accountMenu.edit')}
              onClick={() => {
                onMobileClose?.();
                navigate('/account');
              }}
              className="flex-1 min-w-0 text-left rounded-lg px-1 py-0.5 hover:bg-admin-sidebar-hover transition-colors"
            >
              <div className="flex items-center gap-3">
                {user.avatarUrl ? (
                  <img
                    src={user.avatarUrl}
                    alt=""
                    className="w-8 h-8 rounded-full object-cover border border-admin-border"
                  />
                ) : (
                  <div className="w-8 h-8 rounded-full bg-admin-sidebar-active text-admin-sidebar-active-text flex items-center justify-center text-xs font-bold uppercase">
                    {displayName.slice(0, 2)}
                  </div>
                )}
                <div className="flex-1 min-w-0">
                  <div className="text-xs font-semibold text-admin-sidebar-text truncate">{displayName}</div>
                  <div className="flex items-center gap-1">
                    <Shield className="w-3 h-3 text-admin-primary" />
                    <span className="text-[10px] text-admin-sidebar-muted capitalize">{roleLabel}</span>
                  </div>
                </div>
              </div>
            </button>
            <TeamChatNotificationBeacon variant="sidebar" />
            <DeskNotificationBeacon variant="sidebar" />
          </div>
        )}

        <div className="shrink-0 border-b border-admin-border px-2 py-2">
          {primaryItems.map((item) => renderItem(item))}
        </div>

        <nav className="p-2 space-y-2 overflow-y-auto flex-1 min-h-0">
          {visibleSections.map((section) => {
            const sectionOpen = collapsed || openSections[section.id] !== false;
            const sectionActive = section.items.some((item) => isItemActive(item.href));

            return (
              <div key={section.id} className="space-y-1">
                {!collapsed && (
                  <button
                    type="button"
                    onClick={() =>
                      setOpenSections((prev) => ({ ...prev, [section.id]: !sectionOpen }))
                    }
                    className={`w-full flex items-center gap-2 px-2 py-1.5 rounded-md text-[10px] font-bold uppercase tracking-wider ${
                      sectionActive
                        ? 'text-admin-primary'
                        : 'text-admin-sidebar-muted hover:text-admin-sidebar-text'
                    }`}
                  >
                    <ChevronDown
                      className={`w-3.5 h-3.5 transition-transform ${sectionOpen ? '' : '-rotate-90'}`}
                    />
                    <span className="flex-1 text-left">{t(section.labelKey)}</span>
                  </button>
                )}
                {(collapsed || sectionOpen) && (
                  <div className={`space-y-0.5 ${collapsed ? '' : 'pl-1'}`}>
                    {section.items.map((item) => renderItem(item))}
                  </div>
                )}
              </div>
            );
          })}
        </nav>
      </div>

      <div className="p-3 border-t border-admin-border shrink-0">
        {!collapsed ? (
          <div className="admin-sidebar-footer-card rounded-lg p-3">
            <div className="admin-sidebar-footer-title flex items-center gap-2 font-semibold text-xs mb-1">
              <Database className="w-3.5 h-3.5" />
              <span>{t('admin.sidebar.storageTitle')}</span>
            </div>
            <p className="admin-sidebar-footer-hint text-[10px] leading-relaxed">{t('admin.sidebar.storageHint')}</p>
          </div>
        ) : (
          <div className="flex justify-center text-admin-primary" title={t('admin.sidebar.storageTitle')}>
            <Database className="w-5 h-5" />
          </div>
        )}

        <button
          type="button"
          onClick={onToggleCollapse}
          className="w-full mt-2 p-2 rounded-lg hover:bg-admin-sidebar-hover text-admin-sidebar-muted hover:text-admin-sidebar-text flex items-center justify-center transition-colors cursor-pointer"
          title={collapsed ? t('admin.sidebar.expandPanel') : t('admin.sidebar.collapsePanel')}
        >
          {collapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
          {!collapsed && <span className="text-xs font-semibold ml-2">{t('admin.sidebar.collapsePanel')}</span>}
        </button>
      </div>
    </aside>
  );
};

export default AdminSidebar;
