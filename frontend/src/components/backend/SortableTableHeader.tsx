import React from 'react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import type { SortDirection } from '../../hooks/useColumnSort';

export interface SortableHeaderProps {
  label: string;
  field: string;
  activeField: string;
  direction: SortDirection;
  onSort: (field: string) => void;
  className?: string;
}

export const SortableHeaderButton: React.FC<SortableHeaderProps> = ({
  label,
  field,
  activeField,
  direction,
  onSort,
  className = '',
}) => {
  const active = activeField === field;

  return (
    <button
      type="button"
      className={`inline-flex items-center gap-1.5 text-left font-medium text-gray-600 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors ${className}`}
      onClick={() => onSort(field)}
      aria-label={`Zoradiť podľa ${label}${active ? ` (${direction === 'asc' ? 'vzostupne' : 'zostupne'})` : ''}`}
    >
      <span>{label}</span>
      <span className="inline-flex flex-col shrink-0 leading-none">
        <ArrowUp
          className={`w-3 h-3 ${active && direction === 'asc' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-300 dark:text-gray-600'}`}
          aria-hidden
        />
        <ArrowDown
          className={`w-3 h-3 -mt-1 ${active && direction === 'desc' ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-300 dark:text-gray-600'}`}
          aria-hidden
        />
      </span>
    </button>
  );
};

export const SortableTableHeader: React.FC<SortableHeaderProps & { thClassName?: string }> = ({
  thClassName = '',
  ...props
}) => (
  <th className={thClassName}>
    <SortableHeaderButton {...props} />
  </th>
);

export interface AdminListSortBarProps {
  columns: Array<{ field: string; label: string }>;
  activeField: string;
  direction: SortDirection;
  onSort: (field: string) => void;
}

/** Kompaktný sort bar pre moduly bez tabuľky (správy, komentáre). */
export const AdminListSortBar: React.FC<AdminListSortBarProps> = ({
  columns,
  activeField,
  direction,
  onSort,
}) => (
  <div className="flex flex-wrap items-center gap-2 sm:gap-3 text-xs sm:text-sm">
    <span className="text-gray-500 dark:text-gray-400 shrink-0">Zoradiť:</span>
    {columns.map((column) => (
      <SortableHeaderButton
        key={column.field}
        label={column.label}
        field={column.field}
        activeField={activeField}
        direction={direction}
        onSort={onSort}
        className="px-2 py-1 rounded-md bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700"
      />
    ))}
  </div>
);
