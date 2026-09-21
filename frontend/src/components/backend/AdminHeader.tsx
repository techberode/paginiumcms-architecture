// frontend/src/components/backend/AdminHeader.tsx
import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Database, Globe, LogOut, Menu, PanelLeftClose, PanelLeftOpen, Search, Zap } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useCachePurge } from '../../hooks/useCachePurge';
import { useI18n } from '../../context/I18nContext';
import { AdminThemeToggle } from './AdminThemeToggle';
import { AdminAccountMenu } from './AdminAccountMenu';
import { DeskNotificationBeacon } from './DeskNotificationBeacon';
import { TeamChatNotificationBeacon } from './TeamChatNotificationBeacon';

interface AdminHeaderProps {
  onGoToWebsite: () => void;
  onOpenMobileMenu?: () => void;
  onOpenChangePassword?: () => void;
  onOpenCommandPalette?: () => void;
  sidebarCollapsed?: boolean;
  onToggleSidebar?: () => void;
  navPlacement?: 'side' | 'top';
}

function resolveTabId(pathname: string): string {
  const parts = pathname.split('/').filter(Boolean);
  if (parts[0] === 'platform' && parts[1]) {
    return parts[1];
  }

  return parts[0] || 'dashboard';
}

export const AdminHeader: React.FC<AdminHeaderProps> = ({
  onGoToWebsite,
  onOpenMobileMenu,
  onOpenChangePassword,
  onOpenCommandPalette,
  sidebarCollapsed = false,
  onToggleSidebar,
  navPlacement = 'side',
}) => {
  const { t } = useI18n();
  const location = useLocation();
  const navigate = useNavigate();
  const { logout } = useAuth();
  const { purge, isPurging } = useCachePurge();
  const tabId = resolveTabId(location.pathname);
  const tabTitleKey = `admin.header.tabs.${tabId}`;
  const tabLabel =
    t(tabTitleKey) !== tabTitleKey ? t(tabTitleKey) : t('admin.header.fallbackTitle');

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login');
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <header
      data-testid="admin-topbar"
      className="admin-topbar h-16 shrink-0 px-4 sm:px-6 flex items-center justify-between gap-4 z-40"
    >
      <div className="flex items-center gap-3 min-w-0">
        {navPlacement === 'side' && onToggleSidebar && (
          <button
            type="button"
            onClick={onToggleSidebar}
            className="admin-topbar-ghost hidden lg:inline-flex p-2 rounded-lg"
            title={sidebarCollapsed ? t('admin.sidebar.expandPanel') : t('admin.sidebar.collapsePanel')}
            aria-label={sidebarCollapsed ? t('admin.sidebar.expandPanel') : t('admin.sidebar.collapsePanel')}
          >
            {sidebarCollapsed ? <PanelLeftOpen className="w-5 h-5" /> : <PanelLeftClose className="w-5 h-5" />}
          </button>
        )}
        {onOpenMobileMenu && (
          <button
            type="button"
            onClick={onOpenMobileMenu}
            className="admin-topbar-ghost lg:hidden p-2 rounded-lg"
            aria-label={t('admin.header.openMenu')}
          >
            <Menu className="w-5 h-5" />
          </button>
        )}
        <div className="min-w-0">
          <div className="admin-topbar-kicker flex items-center gap-2 text-[11px] font-semibold tracking-wider uppercase">
            <span>{t('admin.header.engineBrand')}</span>
            <span>/</span>
            <span className="admin-topbar-accent font-bold">{tabId}</span>
          </div>
          <h1 className="admin-topbar-title text-lg sm:text-xl font-bold tracking-tight mt-0.5 truncate">
            {tabLabel}
          </h1>
        </div>
      </div>

      <div className="flex items-center gap-2 sm:gap-3 shrink-0">
        {onOpenCommandPalette && (
          <button
            type="button"
            onClick={onOpenCommandPalette}
            title={t('platform.commandPalette.openShortcut')}
            className="admin-topbar-control hidden md:flex items-center gap-2 min-w-[12rem] lg:min-w-[16rem] px-3 py-2 rounded-lg text-xs font-medium transition-colors"
          >
            <Search className="w-4 h-4 shrink-0 text-admin-primary" />
            <span className="truncate flex-1 text-left">{t('platform.commandPalette.headerPlaceholder')}</span>
            <kbd className="hidden lg:inline text-[10px] font-bold px-1.5 py-0.5 rounded bg-admin-card border border-admin-border text-admin-muted">
              Ctrl+K
            </kbd>
          </button>
        )}

        <AdminThemeToggle />

        <span className="hidden lg:flex items-center gap-1.5 bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 px-3 py-1.5 rounded-lg text-xs font-bold border border-emerald-200 dark:border-emerald-800">
          <Zap className="w-3.5 h-3.5 text-emerald-500 fill-emerald-500" />
          <span>{t('admin.header.apiMode')}</span>
        </span>

        <button
          type="button"
          onClick={() => void purge('content')}
          disabled={isPurging}
          title={t('admin.header.purgeCacheTitle')}
          className="admin-topbar-control flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer"
        >
          <Database className={`w-4 h-4 text-admin-primary ${isPurging ? 'animate-pulse' : ''}`} />
          <span className="hidden sm:inline">
            {isPurging ? t('settings.cache.purging') : t('admin.header.purgeCache')}
          </span>
        </button>

        <button
          type="button"
          onClick={onGoToWebsite}
          className="admin-btn-primary flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all cursor-pointer"
        >
          <Globe className="w-4 h-4" />
          <span className="hidden sm:inline">{t('admin.header.viewWebsite')}</span>
        </button>

        <TeamChatNotificationBeacon variant="header" />
        <DeskNotificationBeacon variant="header" />
        <AdminAccountMenu variant="header" onOpenChangePassword={onOpenChangePassword} />

        <button
          type="button"
          onClick={() => void handleLogout()}
          title={t('admin.header.logout')}
          className="admin-topbar-ghost p-2 rounded-lg transition-colors cursor-pointer"
        >
          <LogOut className="w-5 h-5" />
        </button>
      </div>
    </header>
  );
};

export default AdminHeader;
