// frontend/src/components/backend/SeoHealthBadge.tsx
import React from 'react';
import { type SeoHealthLevel, type SeoIssue } from '../../utils/seoHealth';
import { useI18n } from '../../context/I18nContext';

const STYLE: Record<SeoHealthLevel, string> = {
  ok: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  warning: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  critical: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
};

export interface SeoHealthBadgeProps {
  level: SeoHealthLevel;
  issues?: SeoIssue[];
  className?: string;
}

export const SeoHealthBadge: React.FC<SeoHealthBadgeProps> = ({ level, issues = [], className = '' }) => {
  const { t } = useI18n();
  const label = t(`media.seo.${level}`);
  const issueSummary =
    issues.length > 0
      ? issues.map((issue) => t(`editor.seo.issues.${issue.code}.title`)).join(' · ')
      : label;

  return (
    <span
      className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${STYLE[level]} ${className}`}
      title={issueSummary}
    >
      {label}
    </span>
  );
};

export default SeoHealthBadge;
