import React from 'react';
import { AdminStatusBadge } from './AdminStatusBadge';
import { toneFromProbeStatus, type AdminStatusTone } from '../../utils/adminStatusKind';

export interface AdminProbeRowProps {
  label: React.ReactNode;
  detail?: React.ReactNode;
  status?: string | boolean | null;
  tone?: AdminStatusTone;
  dotOnly?: boolean;
  className?: string;
}

export const AdminProbeRow: React.FC<AdminProbeRowProps> = ({
  label,
  detail,
  status,
  tone,
  dotOnly = false,
  className = '',
}) => {
  const resolved = tone ?? toneFromProbeStatus(status);

  return (
    <li
      className={`flex items-start justify-between gap-3 border-b border-gray-200/80 py-2 last:border-0 dark:border-gray-700/80 ${className}`.trim()}
    >
      <div className="min-w-0 flex-1">
        <div className="text-sm font-medium text-gray-800 dark:text-gray-100">{label}</div>
        {detail ? (
          <div className="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{detail}</div>
        ) : null}
      </div>
      <AdminStatusBadge tone={resolved} dotOnly={dotOnly} className="mt-0.5 shrink-0" />
    </li>
  );
};
