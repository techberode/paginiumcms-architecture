import React, { useContext, useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { ChevronDown, CircleUser, KeyRound, LogOut, Shield, UserRound } from 'lucide-react';
import { AuthContext } from '../../context/AuthContext';
import { useI18n } from '../../context/I18nContext';

interface AdminAccountMenuProps {
  variant: 'header' | 'topnav';
  onOpenChangePassword?: () => void;
  onNavigate?: () => void;
}

export const AdminAccountMenu: React.FC<AdminAccountMenuProps> = ({
  variant,
  onOpenChangePassword,
  onNavigate,
}) => {
  const { t } = useI18n();
  const auth = useContext(AuthContext);
  const user = auth?.user ?? null;
  const navigate = useNavigate();
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement>(null);
  const displayName = user?.name || t('admin.header.administrator');

  useEffect(() => {
    const onDoc = (event: MouseEvent) => {
      if (!rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', onDoc);
    document.addEventListener('keydown', onKey);
    return () => {
      document.removeEventListener('mousedown', onDoc);
      document.removeEventListener('keydown', onKey);
    };
  }, []);

  if (!user) {
    return null;
  }

  const go = (path: string) => {
    setOpen(false);
    onNavigate?.();
    navigate(path);
  };

  const handleLogout = async () => {
    setOpen(false);
    await auth?.logout();
    navigate('/login');
  };

  const compact = variant === 'topnav';

  return (
    <div ref={rootRef} className="relative shrink-0" data-testid="admin-account-menu">
      <button
        type="button"
        aria-expanded={open}
        aria-haspopup="menu"
        data-testid="admin-account-trigger"
        onClick={() => setOpen((value) => !value)}
        className={
          compact
            ? `inline-flex items-center gap-2 px-3 py-2 rounded-lg text-[13px] font-semibold transition-colors ${
                open
                  ? 'text-admin-sidebar-active-text bg-admin-sidebar-active'
                  : 'text-admin-sidebar-text hover:bg-admin-sidebar-hover'
              }`
            : 'admin-topbar-identity hidden sm:flex items-center gap-2.5 pl-2 border-l text-xs font-semibold'
        }
      >
        {user.avatarUrl ? (
          <img
            src={user.avatarUrl}
            alt=""
            className={compact ? 'w-6 h-6 rounded-md object-cover border border-admin-border' : 'w-8 h-8 rounded-lg object-cover border border-admin-border'}
          />
        ) : (
          <div
            className={
              compact
                ? 'w-6 h-6 rounded-md bg-admin-primary text-white flex items-center justify-center'
                : 'w-8 h-8 rounded-lg bg-admin-primary text-white flex items-center justify-center shadow-sm'
            }
          >
            <Shield className={compact ? 'w-3.5 h-3.5' : 'w-4 h-4'} />
          </div>
        )}
        <span className={compact ? 'max-w-[8rem] truncate' : 'hidden xl:inline max-w-[9rem] truncate'}>
          {displayName}
        </span>
        <ChevronDown className={`w-3.5 h-3.5 ${open ? 'rotate-180' : ''}`} />
      </button>

      {open ? (
        <div
          role="menu"
          data-testid="admin-account-dropdown"
          className={`admin-account-dropdown absolute mt-2 min-w-[14rem] rounded-xl p-1 ${
            compact ? 'left-0' : 'right-0'
          }`}
        >
          <button type="button" role="menuitem" className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm" onClick={() => go('/account')}>
            <UserRound className="w-4 h-4" />
            {t('admin.accountMenu.edit')}
          </button>
          <button type="button" role="menuitem" className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm" onClick={() => go('/account/public')}>
            <CircleUser className="w-4 h-4" />
            {t('admin.accountMenu.public')}
          </button>
          <button type="button" role="menuitem" className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm" onClick={() => go('/account/security')}>
            <Shield className="w-4 h-4" />
            {t('admin.accountMenu.security')}
          </button>
          {onOpenChangePassword ? (
            <button
              type="button"
              role="menuitem"
              className="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm"
              onClick={() => {
                setOpen(false);
                onOpenChangePassword();
              }}
            >
              <KeyRound className="w-4 h-4" />
              {t('admin.header.changePassword')}
            </button>
          ) : null}
          <button type="button" role="menuitem" className="admin-account-dropdown-logout w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm" onClick={() => void handleLogout()}>
            <LogOut className="w-4 h-4" />
            {t('admin.header.logout')}
          </button>
        </div>
      ) : null}
    </div>
  );
};

export default AdminAccountMenu;
