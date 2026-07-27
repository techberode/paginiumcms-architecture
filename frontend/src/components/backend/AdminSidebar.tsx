// frontend/src/components/backend/AdminSidebar.tsx
import React, { useMemo, useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import {
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Database,
  Shield,
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useAdminCounts } from '../../hooks/useAdminCounts';
import { useSettings } from '../../hooks/useSettings';
import { useI18n } from '../../context/I18nContext';
import {
  ADMIN_DEFAULT_ROUTE,
  ADMIN_NAV_ANALYTICS_ITEM,
  ADMIN_NAV_PRIMARY_ITEM,
  ADMIN_NAV_SECTIONS,
} from '../../config/adminNavSections';
import type { AdminNavItemDef } from '../../config/adminNavTypes';
import { SiteLogo } from '../branding/SiteLogo';

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
  const { settings } = useSettings();
  const siteName = String(settings?.general?.siteName ?? 'PaginiumCMS');
  const { counts, showListCounts } = useAdminCounts();
  const location = useLocation();
  const isAdmin = user?.roles?.some((r) => r === 'ADMIN' || r === 'SUPER_ADMIN') ?? false;
  const isSuperAdmin = user?.roles?.includes('SUPER_ADMIN') ?? false;
  const isDemoInstance = settings?.demo?.enabled === true;
  const [openSections, setOpenSections] = useState<Record<string, boolean>>(() =>
    Object.fromEntries(ADMIN_NAV_SECTIONS.map((section) => [section.id, true]))
  );

  const countFor = (id: string): number | undefined => {
    if (!showListCounts || !counts) {
      return undefined;
    }
    const map: Record<string, number | undefined> = {
      pages: counts.pages,
      articles: counts.articles,
      media: counts.media,
      comments: counts.comments,
      messages: counts.messages,
      backups: counts.backups,
      trash: counts.trash,
      users: counts.users,
      firewall: counts.firewall_jails,
    };
    return map[id];
  };

  const isItemActive = (href: string): boolean => {
    if (href === ADMIN_DEFAULT_ROUTE) {
      return location.pathname === ADMIN_DEFAULT_ROUTE;
    }
    return location.pathname === href || location.pathname.startsWith(`${href}/`);
  };

  const visibleSections = useMemo(
    () =>
      ADMIN_NAV_SECTIONS.map((section) => ({
        ...section,
        items: section.items.filter((item) => {
          if (item.superAdminOnly && !isSuperAdmin) {
            return false;
          }
          if (item.adminOnly && !isAdmin) {
            return false;
          }
          if (item.hideOnDemoInstance && isDemoInstance) {
            return false;
          }
          return true;
        }),
      })).filter((section) => section.items.length > 0),
    [isAdmin, isSuperAdmin, isDemoInstance]
  );

  const displayName = user?.name || t('admin.sidebar.userFallback');
  const roleKey = (user?.roles?.[0] ?? 'editor').toLowerCase();
  const roleLabel = t(`admin.roles.${roleKey}`) !== `admin.roles.${roleKey}`
    ? t(`admin.roles.${roleKey}`)
    : roleKey;

  const linkClass = (isActive: boolean) =>
    `w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer group relative ${
      isActive
        ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25 font-extrabold'
        : 'hover:bg-slate-800/80 hover:text-white text-slate-400'
    }`;

  const renderItem = (item: AdminNavItemDef) => {
    const Icon = item.icon;
    const label = t(item.labelKey);
    const count = countFor(item.id);

    return (
      <NavLink
        key={item.id}
        to={item.href}
        title={collapsed ? label : undefined}
        onClick={onMobileClose}
        className={() => linkClass(isItemActive(item.href))}
      >
        <Icon className="w-4 h-4 shrink-0 transition-transform group-hover:scale-110" />
        {!collapsed && <span className="flex-1 text-left line-clamp-1">{label}</span>}
        {!collapsed && count !== undefined && (
          <span className="px-2 py-0.5 rounded-full text-[10px] font-black ml-auto bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-slate-200">
            {count}
          </span>
        )}
      </NavLink>
    );
  };

  return (
    <aside
      className={`bg-slate-900 text-slate-300 border-r border-slate-800 transition-all flex flex-col justify-between select-none shrink-0 z-50
        ${collapsed ? 'w-[4.5rem]' : 'w-64'}
        fixed inset-y-0 left-0 lg:static
        ${mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
      `}
    >
      <div className="min-h-0 flex flex-col">
        <div className="h-16 px-4 border-b border-slate-800 flex items-center gap-3 overflow-hidden shrink-0">
          <SiteLogo
            showName={false}
            className="flex items-center gap-3 min-w-0"
            imageClassName="h-10 w-auto max-w-[120px] object-contain shrink-0"
            fallbackClassName="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-indigo-500/25 shrink-0"
          />
          {!collapsed && (
            <div className="min-w-0">
              <span className="font-black text-lg text-white block leading-none truncate">{siteName}</span>
              <span className="text-[10px] font-extrabold text-indigo-400 uppercase tracking-wider block mt-1 truncate">
                {t('admin.sidebar.brandSubtitle')}
              </span>
            </div>
          )}
        </div>

        {user && !collapsed && (
          <div className="px-4 py-3 border-b border-slate-800/50 shrink-0">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white text-xs font-bold uppercase">
                {displayName.slice(0, 2)}
              </div>
              <div className="flex-1 min-w-0">
                <div className="text-xs font-bold text-white truncate">{displayName}</div>
                <div className="flex items-center gap-1">
                  <Shield className="w-3 h-3 text-indigo-400" />
                  <span className="text-[10px] text-slate-400 capitalize">{roleLabel}</span>
                </div>
              </div>
            </div>
          </div>
        )}

        <div className={`shrink-0 border-b border-slate-800/50 ${collapsed ? 'px-2 py-2' : 'px-2 py-2'}`}>
          {renderItem(ADMIN_NAV_PRIMARY_ITEM)}
          {(!ADMIN_NAV_ANALYTICS_ITEM.adminOnly || isAdmin) && renderItem(ADMIN_NAV_ANALYTICS_ITEM)}
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
                    className={`w-full flex items-center gap-2 px-2 py-1.5 rounded-lg text-[10px] font-extrabold uppercase tracking-wider ${
                      sectionActive ? 'text-indigo-300' : 'text-slate-500 hover:text-slate-300'
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

      <div className="p-3 border-t border-slate-800/80 shrink-0">
        {!collapsed ? (
          <div className="bg-slate-800/60 rounded-2xl p-3 border border-slate-700/50">
            <div className="flex items-center gap-2 text-indigo-400 font-extrabold text-xs mb-1">
              <Database className="w-3.5 h-3.5" />
              <span>{t('admin.sidebar.storageTitle')}</span>
            </div>
            <p className="text-[10px] text-slate-400 leading-relaxed">{t('admin.sidebar.storageHint')}</p>
          </div>
        ) : (
          <div className="flex justify-center text-indigo-400" title={t('admin.sidebar.storageTitle')}>
            <Database className="w-5 h-5" />
          </div>
        )}

        <button
          type="button"
          onClick={onToggleCollapse}
          className="w-full mt-2 p-2 rounded-xl hover:bg-slate-800 text-slate-500 hover:text-slate-300 flex items-center justify-center transition-colors cursor-pointer"
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
