// frontend/src/components/backend/AdminInboxList.tsx
import React from 'react';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useI18n } from '../../context/I18nContext';

export interface AdminInboxListProps {
  children: React.ReactNode;
}

export const AdminInboxList: React.FC<AdminInboxListProps> = ({ children }) => (
  <div className="rounded-lg border border-admin-border overflow-hidden bg-admin-card shadow-admin">
    {children}
  </div>
);

interface AdminInboxListHeaderProps {
  allSelected: boolean;
  onToggleAll: () => void;
  label?: string;
}

export const AdminInboxListHeader: React.FC<AdminInboxListHeaderProps> = ({
  allSelected,
  onToggleAll,
  label,
}) => {
  const { t } = useI18n();
  const resolvedLabel = label ?? t('list.inbox.selectAllOnPage');

  return (
  <div className="flex items-center gap-3 px-4 py-2.5 border-b border-admin-border bg-admin-canvas">
    <input
      type="checkbox"
      checked={allSelected}
      onChange={onToggleAll}
      aria-label={resolvedLabel}
      className="rounded border-gray-300 dark:border-gray-600"
    />
    <span className="text-xs text-admin-muted">{resolvedLabel}</span>
  </div>
  );
};

export interface AdminInboxRowProps {
  id: string;
  index: number;
  expanded: boolean;
  onToggleExpand: (id: string) => void;
  selected: boolean;
  onToggleSelect: (id: string) => void;
  unread?: boolean;
  summary: React.ReactNode;
  detail: React.ReactNode;
  actions?: React.ReactNode;
}

export const AdminInboxRow: React.FC<AdminInboxRowProps> = ({
  id,
  index,
  expanded,
  onToggleExpand,
  selected,
  onToggleSelect,
  unread = false,
  summary,
  detail,
  actions,
}) => {
  const { t } = useI18n();
  const stripe =
    index % 2 === 0 ? 'bg-admin-card' : 'bg-admin-canvas/80';

  return (
    <div className={`border-b border-admin-border last:border-b-0 ${stripe}`}>
      <div
        className={`flex items-stretch gap-2 sm:gap-3 px-3 sm:px-4 py-3 cursor-pointer admin-row-hover transition-colors ${
          selected ? 'ring-1 ring-inset ring-admin-primary/40' : ''
        } ${unread ? 'font-semibold' : ''}`}
      >
        <div className="flex items-start pt-0.5 shrink-0">
          <input
            type="checkbox"
            checked={selected}
            onChange={() => onToggleSelect(id)}
            onClick={(e) => e.stopPropagation()}
            aria-label={t('list.inbox.selectItem')}
            className="rounded border-gray-300 dark:border-gray-600 mt-1"
          />
        </div>

        <button
          type="button"
          className="flex items-start gap-2 flex-1 min-w-0 text-left"
          onClick={() => onToggleExpand(id)}
          aria-expanded={expanded}
        >
          <span className="mt-1 shrink-0 text-admin-muted">
            {expanded ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
          </span>
          <div className="flex-1 min-w-0">{summary}</div>
        </button>

        {actions ? <div className="hidden sm:flex items-center shrink-0">{actions}</div> : null}
      </div>

      {expanded ? (
        <div className="px-4 pb-4 pl-12 sm:pl-14 space-y-3 border-t border-admin-border bg-admin-canvas/50">
          {detail}
          {actions ? <div className="flex flex-wrap gap-2 sm:hidden">{actions}</div> : null}
        </div>
      ) : null}
    </div>
  );
};
