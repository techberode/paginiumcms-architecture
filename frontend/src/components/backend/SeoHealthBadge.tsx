// frontend/src/components/backend/SeoHealthBadge.tsx
import React from 'react';
import { seoHealthLabel, type SeoHealthLevel } from '../../utils/seoHealth';

const STYLE: Record<SeoHealthLevel, string> = {
  ok: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  warning: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  critical: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};

export interface SeoHealthBadgeProps {
  level: SeoHealthLevel;
  className?: string;
}

export const SeoHealthBadge: React.FC<SeoHealthBadgeProps> = ({ level, className = '' }) => (
  <span
    className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STYLE[level]} ${className}`}
    title={seoHealthLabel(level)}
  >
    {seoHealthLabel(level)}
  </span>
);

export default SeoHealthBadge;
