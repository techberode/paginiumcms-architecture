import React from 'react';
import { useI18n } from '../../context/I18nContext';
import type { AdminStatusTone } from '../../utils/adminStatusKind';

export interface AdminStatusBadgeProps {
  tone: AdminStatusTone;
  /** Override visible label (e.g. version string). */
  label?: string;
  /** Dot only — no text chip (still has aria-label). */
  dotOnly?: boolean;
  className?: string;
}

const TONE_CLASS: Record<AdminStatusTone, string> = {
  active: 'bg-emerald-100 text-emerald-900 ring-emerald-600/30 dark:bg-emerald-950/50 dark:text-emerald-200',
  inactive: 'bg-slate-200 text-slate-700 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300',
  available: 'bg-emerald-100 text-emerald-900 ring-emerald-600/30 dark:bg-emerald-950/50 dark:text-emerald-200',
  unavailable: 'bg-red-100 text-red-900 ring-red-600/30 dark:bg-red-950/50 dark:text-red-200',
  neutral: 'bg-slate-100 text-slate-600 ring-slate-400/20 dark:bg-slate-900 dark:text-slate-400',
};

const DOT_CLASS: Record<AdminStatusTone, string> = {
  active: 'bg-emerald-500',
  inactive: 'bg-slate-400',
  available: 'bg-emerald-500',
  unavailable: 'bg-red-500',
  neutral: 'bg-slate-400',
};

export const AdminStatusBadge: React.FC<AdminStatusBadgeProps> = ({
  tone,
  label,
  dotOnly = false,
  className = '',
}) => {
  const { t } = useI18n();
  const text = label ?? t(`admin.status.${tone}`);

  if (dotOnly) {
    return (
      <span
        className={`inline-block h-2.5 w-2.5 shrink-0 rounded-full ${DOT_CLASS[tone]} ${className}`.trim()}
        title={text}
        aria-label={text}
        role="status"
      />
    );
  }

  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset ${TONE_CLASS[tone]} ${className}`.trim()}
      role="status"
    >
      <span className={`h-1.5 w-1.5 shrink-0 rounded-full ${DOT_CLASS[tone]}`} aria-hidden />
      {text}
    </span>
  );
};
