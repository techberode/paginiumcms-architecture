import React from 'react';
import { NavLink } from 'react-router-dom';
import type { LucideIcon } from 'lucide-react';
import { ADMIN_TAB_ACTIVE, ADMIN_TAB_ITEM, ADMIN_TAB_LIST } from '../../theme/adminUiClasses';

export interface AdminTabItem {
  id: string;
  label: string;
  to?: string;
  end?: boolean;
  testId?: string;
  icon?: LucideIcon;
}

export function adminTabClass(active: boolean): string {
  return active ? `${ADMIN_TAB_ITEM} ${ADMIN_TAB_ACTIVE}` : ADMIN_TAB_ITEM;
}

export interface AdminTabsProps {
  items: AdminTabItem[];
  activeId: string;
  onSelect?: (id: string) => void;
  ariaLabel?: string;
  className?: string;
}

export const AdminTabs: React.FC<AdminTabsProps> = ({
  items,
  activeId,
  onSelect,
  ariaLabel,
  className = '',
}) => (
  <nav className={`${ADMIN_TAB_LIST} ${className}`.trim()} aria-label={ariaLabel}>
    {items.map((item) => {
      const Icon = item.icon;
      const active = item.id === activeId;
      const content = (
        <>
          {Icon ? <Icon className="h-4 w-4 shrink-0" aria-hidden /> : null}
          <span className="admin-tab-label">{item.label}</span>
        </>
      );

      if (item.to) {
        return (
          <NavLink
            key={item.id}
            to={item.to}
            end={item.end}
            data-testid={item.testId}
            aria-current={active ? 'page' : undefined}
            className={() => adminTabClass(active)}
          >
            {content}
          </NavLink>
        );
      }

      return (
        <button
          key={item.id}
          type="button"
          data-testid={item.testId}
          aria-current={active ? 'page' : undefined}
          onClick={() => onSelect?.(item.id)}
          className={adminTabClass(active)}
        >
          {content}
        </button>
      );
    })}
  </nav>
);

export default AdminTabs;
