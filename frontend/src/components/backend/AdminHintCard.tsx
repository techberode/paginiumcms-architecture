import React from 'react';
import { Info, AlertTriangle, ShieldAlert } from 'lucide-react';

type AdminHintTone = 'info' | 'warning' | 'danger';

const toneStyles: Record<
  AdminHintTone,
  { wrap: string; icon: React.ComponentType<{ className?: string }> }
> = {
  info: {
    wrap: 'border-indigo-200 bg-indigo-50 text-indigo-950 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-100',
    icon: Info,
  },
  warning: {
    wrap: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100',
    icon: AlertTriangle,
  },
  danger: {
    wrap: 'border-rose-200 bg-rose-50 text-rose-950 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-100',
    icon: ShieldAlert,
  },
};

export interface AdminHintCardProps {
  tone?: AdminHintTone;
  title?: string;
  children: React.ReactNode;
  className?: string;
}

export const AdminHintCard: React.FC<AdminHintCardProps> = ({
  tone = 'info',
  title,
  children,
  className = '',
}) => {
  const style = toneStyles[tone];
  const Icon = style.icon;

  return (
    <aside
      className={`rounded-2xl border px-4 py-3 text-sm ${style.wrap} ${className}`.trim()}
      role="note"
    >
      <div className="flex gap-3">
        <Icon className="w-5 h-5 shrink-0 mt-0.5 opacity-80" aria-hidden />
        <div className="min-w-0 space-y-1">
          {title && <div className="font-bold leading-snug">{title}</div>}
          <div className="opacity-90 leading-relaxed">{children}</div>
        </div>
      </div>
    </aside>
  );
};

export default AdminHintCard;
