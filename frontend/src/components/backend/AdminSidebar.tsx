// frontend/src/components/backend/AdminSidebar.tsx
import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import {
  LayoutDashboard,
  FileText,
  BookOpen,
  Image as ImageIcon,
  Settings,
  Code,
  History,
  ChevronLeft,
  ChevronRight,
  Database,
  Users,
  ScrollText,
  Shield,
  ShieldCheck,
  Bell,
  GitBranch,
  MessageSquare,
  Mail,
  HardDrive,
  Trash2,
  CalendarClock,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { useAdminCounts } from '../../hooks/useAdminCounts';

interface AdminSidebarProps {
  collapsed: boolean;
  onToggleCollapse: () => void;
  mobileOpen?: boolean;
  onMobileClose?: () => void;
}

interface MenuItem {
  id: string;
  label: string;
  href: string;
  icon: LucideIcon;
  count?: number;
  adminOnly?: boolean;
}

export const AdminSidebar: React.FC<AdminSidebarProps> = ({
  collapsed,
  onToggleCollapse,
  mobileOpen = false,
  onMobileClose,
}) => {
  const { user } = useAuth();
  const { counts, showListCounts } = useAdminCounts();
  const location = useLocation();
  const isAdmin = user?.roles?.some((r) => r === 'ADMIN' || r === 'SUPER_ADMIN') ?? false;

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
    };
    return map[id];
  };

  const isItemActive = (href: string): boolean => {
    if (href === '/dashboard') {
      return location.pathname === '/dashboard';
    }
    return location.pathname === href || location.pathname.startsWith(`${href}/`);
  };

  const displayName = user?.name || 'User';
  const roleLabel = user?.roles?.[0] || 'editor';

  const menuItems: MenuItem[] = [
    { id: 'dashboard', label: 'Prehľad', href: '/dashboard', icon: LayoutDashboard },
    { id: 'pages', label: 'Podstránky', href: '/pages', icon: FileText, count: countFor('pages') },
    { id: 'articles', label: 'Články (Blog)', href: '/articles', icon: BookOpen, count: countFor('articles') },
    { id: 'media', label: 'Médiá', href: '/media', icon: ImageIcon, count: countFor('media') },
    { id: 'navigation', label: 'Navigácia', href: '/navigation', icon: Database },
    { id: 'comments', label: 'Komentáre', href: '/comments', icon: MessageSquare, adminOnly: true, count: countFor('comments') },
    { id: 'messages', label: 'Správy', href: '/messages', icon: Mail, adminOnly: true, count: countFor('messages') },
    { id: 'github', label: 'GitHub', href: '/github', icon: GitBranch, adminOnly: true },
    { id: 'code-editor', label: 'Code Editor', href: '/code-editor', icon: Code },
    { id: 'backups', label: 'Zálohy', href: '/backups', icon: HardDrive, count: countFor('backups') },
    { id: 'trash', label: 'Kôš', href: '/trash', icon: Trash2, adminOnly: true, count: countFor('trash') },
    { id: 'firewall', label: 'Firewall', href: '/firewall', icon: Shield, adminOnly: true, count: countFor('firewall_jails') },
    { id: 'logs', label: 'Logy', href: '/logs', icon: ScrollText, adminOnly: true },
    { id: 'audit', label: 'Audit Trail', href: '/audit', icon: History },
    { id: 'notifications', label: 'Notifikácie', href: '/notifications', icon: Bell },
    { id: 'scheduler', label: 'Plánovač', href: '/scheduler', icon: CalendarClock, adminOnly: true },
    { id: 'users', label: 'Používatelia', href: '/users', icon: Users, adminOnly: true, count: countFor('users') },
    { id: 'account-security', label: 'Bezpečnosť účtu', href: '/account/security', icon: ShieldCheck },
    { id: 'settings', label: 'Nastavenia', href: '/settings', icon: Settings },
  ].filter((item) => !item.adminOnly || isAdmin);

  const linkClass = (isActive: boolean) =>
    `w-full flex items-center gap-3 px-3.5 py-3 rounded-2xl text-xs font-bold transition-all cursor-pointer group relative ${
      isActive
        ? 'bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow-lg shadow-indigo-500/25 font-extrabold'
        : 'hover:bg-slate-800/80 hover:text-white text-slate-400'
    }`;

  return (
    <aside
      className={`bg-slate-900 text-slate-300 border-r border-slate-800 transition-all flex flex-col justify-between select-none shrink-0 z-50
        ${collapsed ? 'w-20' : 'w-72'}
        fixed inset-y-0 left-0 lg:static
        ${mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'}
      `}
    >
      <div>
        <div className="h-16 px-5 border-b border-slate-800 flex items-center justify-between gap-3 overflow-hidden">
          <div className="flex items-center gap-3 min-w-max">
            <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white font-black text-lg shadow-lg shadow-indigo-500/25">
              P
            </div>
            {!collapsed && (
              <div>
                <span className="font-black text-lg text-white block leading-none">Paginium</span>
                <span className="text-[10px] font-extrabold text-indigo-400 uppercase tracking-wider block mt-1">
                  Admin v2.0 FlatFile
                </span>
              </div>
            )}
          </div>
        </div>

        {user && !collapsed && (
          <div className="px-4 py-3 border-b border-slate-800/50">
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

        <nav className="p-3 space-y-1 mt-2 overflow-y-auto max-h-[calc(100vh-16rem)]">
          {menuItems.map((item) => {
            const Icon = item.icon;
            return (
              <NavLink
                key={item.id}
                to={item.href}
                title={collapsed ? item.label : undefined}
                onClick={onMobileClose}
                className={() => linkClass(isItemActive(item.href))}
              >
                <Icon
                  className={`w-5 h-5 shrink-0 transition-transform ${
                    'text-inherit group-hover:scale-110'
                  }`}
                />
                {!collapsed && <span className="flex-1 text-left line-clamp-1">{item.label}</span>}
                {!collapsed && item.count !== undefined && (
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-black ml-auto bg-slate-800 text-slate-400 group-hover:bg-slate-700 group-hover:text-slate-200">
                    {item.count}
                  </span>
                )}
              </NavLink>
            );
          })}
        </nav>
      </div>

      <div className="p-4 border-t border-slate-800/80">
        {!collapsed ? (
          <div className="bg-slate-800/60 rounded-2xl p-4 border border-slate-700/50">
            <div className="flex items-center gap-2 text-indigo-400 font-extrabold text-xs mb-1">
              <Database className="w-3.5 h-3.5" />
              <span>FlatFile Storage</span>
            </div>
            <p className="text-[10px] text-slate-400 leading-relaxed">
              Obsah a nastavenia sa ukladajú do JSON súborov na disku.
            </p>
          </div>
        ) : (
          <div className="flex justify-center text-indigo-400" title="FlatFile Storage">
            <Database className="w-5 h-5" />
          </div>
        )}

        <button
          type="button"
          onClick={onToggleCollapse}
          className="w-full mt-3 p-2 rounded-xl hover:bg-slate-800 text-slate-500 hover:text-slate-300 flex items-center justify-center transition-colors cursor-pointer"
        >
          {collapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
          {!collapsed && <span className="text-xs font-semibold ml-2">Zbaliť panel</span>}
        </button>
      </div>
    </aside>
  );
};

export default AdminSidebar;
