// frontend/src/components/backend/AdminInboxList.tsx
import React from 'react';
import { ChevronDown, ChevronRight } from 'lucide-react';

export interface AdminInboxListProps {
  children: React.ReactNode;
}

export const AdminInboxList: React.FC<AdminInboxListProps> = ({ children }) => (
  <div className="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900/40">
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
  label = 'Vybrať všetky na stránke',
}) => (
  <div className="flex items-center gap-3 px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60">
    <input
      type="checkbox"
      checked={allSelected}
      onChange={onToggleAll}
      aria-label={label}
      className="rounded border-gray-300 dark:border-gray-600"
    />
    <span className="text-xs text-gray-500 dark:text-gray-400">{label}</span>
  </div>
);

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
  const stripe =
    index % 2 === 0
      ? 'bg-white dark:bg-gray-900/20'
      : 'bg-gray-50/90 dark:bg-gray-800/30';

  return (
    <div className={`border-b border-gray-100 dark:border-gray-800 last:border-b-0 ${stripe}`}>
      <div
        className={`flex items-stretch gap-2 sm:gap-3 px-3 sm:px-4 py-3 cursor-pointer hover:bg-indigo-50/60 dark:hover:bg-indigo-950/20 transition-colors ${
          selected ? 'ring-1 ring-inset ring-indigo-400/50' : ''
        } ${unread ? 'font-semibold' : ''}`}
      >
        <div className="flex items-start pt-0.5 shrink-0">
          <input
            type="checkbox"
            checked={selected}
            onChange={() => onToggleSelect(id)}
            onClick={(e) => e.stopPropagation()}
            aria-label="Vybrať položku"
            className="rounded border-gray-300 dark:border-gray-600 mt-1"
          />
        </div>

        <button
          type="button"
          className="flex items-start gap-2 flex-1 min-w-0 text-left"
          onClick={() => onToggleExpand(id)}
          aria-expanded={expanded}
        >
          <span className="mt-1 shrink-0 text-gray-400">
            {expanded ? <ChevronDown className="w-4 h-4" /> : <ChevronRight className="w-4 h-4" />}
          </span>
          <div className="flex-1 min-w-0">{summary}</div>
        </button>

        {actions ? <div className="hidden sm:flex items-center shrink-0">{actions}</div> : null}
      </div>

      {expanded ? (
        <div className="px-4 pb-4 pl-12 sm:pl-14 space-y-3 border-t border-gray-100 dark:border-gray-800/80 bg-white/70 dark:bg-gray-900/30">
          {detail}
          {actions ? <div className="flex flex-wrap gap-2 sm:hidden">{actions}</div> : null}
        </div>
      ) : null}
    </div>
  );
};
