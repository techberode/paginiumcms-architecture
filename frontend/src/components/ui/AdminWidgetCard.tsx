import React from 'react';
import { ADMIN_CARD } from '../../theme/adminUiClasses';

export interface AdminWidgetCardProps {
  title?: string;
  description?: string;
  action?: React.ReactNode;
  children?: React.ReactNode;
  className?: string;
  bodyClassName?: string;
  padded?: boolean;
}

export const AdminWidgetCard: React.FC<AdminWidgetCardProps> = ({
  title,
  description,
  action,
  children,
  className = '',
  bodyClassName = '',
  padded = true,
}) => (
  <section className={`${ADMIN_CARD} overflow-hidden ${className}`.trim()}>
    {(title || action || description) && (
      <header className="flex shrink-0 items-start justify-between gap-3 border-b border-admin-border px-5 py-3.5">
        <div className="min-w-0">
          {title ? <h2 className="text-sm font-semibold tracking-tight text-admin-text">{title}</h2> : null}
          {description ? <p className="text-xs text-admin-muted mt-1 leading-relaxed">{description}</p> : null}
        </div>
        {action ? <div className="shrink-0">{action}</div> : null}
      </header>
    )}
    {children ? (
      <div className={`${padded ? 'p-5' : ''} ${bodyClassName}`.trim()}>{children}</div>
    ) : null}
  </section>
);

export default AdminWidgetCard;
