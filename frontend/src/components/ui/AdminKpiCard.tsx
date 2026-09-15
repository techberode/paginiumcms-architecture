import React from 'react';
import { Link } from 'react-router-dom';
import type { LucideIcon } from 'lucide-react';
import { ADMIN_KPI } from '../../theme/adminUiClasses';

export interface AdminKpiCardProps {
  title: string;
  value: React.ReactNode;
  icon: LucideIcon;
  to?: string;
  footer?: React.ReactNode;
}

export const AdminKpiCard: React.FC<AdminKpiCardProps> = ({ title, value, icon: Icon, to, footer }) => {
  const inner = (
    <>
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <p className="text-[11px] font-semibold uppercase tracking-wider text-admin-muted">{title}</p>
          <p className="mt-2 text-2xl font-bold tracking-tight text-admin-text truncate">{value}</p>
        </div>
        <div className="rounded-lg bg-admin-sidebar-active p-2.5 text-admin-sidebar-active-text shrink-0">
          <Icon className="h-5 w-5" aria-hidden />
        </div>
      </div>
      {footer ? <div className="mt-3 text-xs font-semibold">{footer}</div> : null}
    </>
  );

  if (to) {
    return (
      <Link to={to} className={`${ADMIN_KPI} hover:border-admin-primary`}>
        {inner}
      </Link>
    );
  }

  return <div className={ADMIN_KPI}>{inner}</div>;
};

export default AdminKpiCard;
