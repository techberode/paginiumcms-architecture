import React from 'react';
import { SUPPORTED_LOCALES } from '../../i18n/types';
import type { ContentEditorStatus } from '../../utils/contentScheduling';

interface LocaleStatusBadgesProps {
  localeStatus?: Record<string, ContentEditorStatus>;
  statusLabels: Record<ContentEditorStatus, string>;
}

const STATUS_CLASS: Record<ContentEditorStatus, string> = {
  draft: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  published: 'bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-200',
  archived: 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200',
  scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-200',
};

export const LocaleStatusBadges: React.FC<LocaleStatusBadgesProps> = ({
  localeStatus,
  statusLabels,
}) => {
  if (!localeStatus || Object.keys(localeStatus).length === 0) {
    return null;
  }

  return (
    <div className="flex flex-wrap gap-1">
      {SUPPORTED_LOCALES.filter((code) => localeStatus[code]).map((code) => {
        const status = localeStatus[code] ?? 'draft';
        return (
          <span
            key={code}
            className={`inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${STATUS_CLASS[status]}`}
            title={`${code.toUpperCase()}: ${statusLabels[status] ?? status}`}
          >
            {code}
          </span>
        );
      })}
    </div>
  );
};
