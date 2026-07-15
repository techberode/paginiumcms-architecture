// frontend/src/components/backend/AdminHeader.tsx
import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Globe, LogOut, Shield, Zap, Key, Menu } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';

interface AdminHeaderProps {
  onGoToWebsite: () => void;
  onOpenMobileMenu?: () => void;
  onOpenChangePassword?: () => void;
}

const TAB_LABELS: Record<string, string> = {
  dashboard: 'Prehľadový Dashboard',
  pages: 'Správa Podstránok',
  articles: 'Správa Blogových Článkov',
  media: 'Knižnica Mediálnych Súborov',
  'code-editor': 'Code Editor',
  backups: 'Správa Záloh',
  audit: 'Audit Trail',
  notifications: 'Notifikácie a Monitoring',
  users: 'Správa Používateľov',
  settings: 'Systémové Nastavenia',
};

function resolveTabId(pathname: string): string {
  const segment = pathname.split('/').filter(Boolean)[0] || 'dashboard';
  return segment;
}

function resolveTabLabel(pathname: string): string {
  const tabId = resolveTabId(pathname);
  return TAB_LABELS[tabId] ?? 'Administrácia';
}

export const AdminHeader: React.FC<AdminHeaderProps> = ({
  onGoToWebsite,
  onOpenMobileMenu,
  onOpenChangePassword,
}) => {
  const location = useLocation();
  const navigate = useNavigate();
  const { logout } = useAuth();
  const tabId = resolveTabId(location.pathname);
  const tabLabel = resolveTabLabel(location.pathname);

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/login');
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <header className="h-16 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 sm:px-8 flex items-center justify-between gap-4 sticky top-0 z-30 transition-colors">
      <div className="flex items-center gap-3 min-w-0">
        {onOpenMobileMenu && (
          <button
            type="button"
            onClick={onOpenMobileMenu}
            className="lg:hidden p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"
            aria-label="Open menu"
          >
            <Menu className="w-5 h-5" />
          </button>
        )}
        <div className="min-w-0">
          <div className="flex items-center gap-2 text-[11px] font-bold tracking-wider text-slate-400 uppercase">
            <span>Paginium Engine</span>
            <span>/</span>
            <span className="text-indigo-600 dark:text-indigo-400 font-extrabold">{tabId}</span>
          </div>
          <h1 className="text-lg sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight mt-0.5 truncate">
            {tabLabel}
          </h1>
        </div>
      </div>

      <div className="flex items-center gap-2 sm:gap-3 shrink-0">
        <span className="hidden lg:flex items-center gap-1.5 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 px-3 py-1.5 rounded-xl text-xs font-extrabold border border-emerald-200/60 dark:border-emerald-800/80">
          <Zap className="w-3.5 h-3.5 text-emerald-500 fill-emerald-500" />
          <span>API Režim</span>
        </span>

        <button
          type="button"
          onClick={onGoToWebsite}
          className="flex items-center gap-2 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 px-4 py-2 rounded-xl text-xs font-extrabold transition-all cursor-pointer"
        >
          <Globe className="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
          <span className="hidden sm:inline">Zobraziť web</span>
        </button>

        {onOpenChangePassword && (
          <button
            type="button"
            onClick={onOpenChangePassword}
            title="Zmeniť heslo"
            className="p-2 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-xl transition-colors"
          >
            <Key className="w-5 h-5" />
          </button>
        )}

        <div className="hidden sm:flex items-center gap-2.5 pl-2 border-l border-slate-200 dark:border-slate-800 text-xs font-bold text-slate-800 dark:text-slate-200">
          <div className="w-8 h-8 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 flex items-center justify-center shadow">
            <Shield className="w-4 h-4" />
          </div>
          <span className="hidden xl:inline">Administrátor</span>
        </div>

        <button
          type="button"
          onClick={() => void handleLogout()}
          title="Odhlásiť z administrácie"
          className="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-xl transition-colors cursor-pointer"
        >
          <LogOut className="w-5 h-5" />
        </button>
      </div>
    </header>
  );
};

export default AdminHeader;
