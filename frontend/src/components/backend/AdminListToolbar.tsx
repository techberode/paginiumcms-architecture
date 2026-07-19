import React from 'react';
import { AdminViewModeToggle } from './AdminViewModeToggle';
import type { AdminViewMode } from '../../hooks/useAdminViewMode';

export interface AdminStatusOption {
  value: string;
  label: string;
}

export interface AdminListToolbarProps {
  search: string;
  onSearchChange: (value: string) => void;
  searchPlaceholder?: string;
  statusFilter?: string;
  onStatusFilterChange?: (value: string) => void;
  statusOptions?: AdminStatusOption[];
  viewMode?: AdminViewMode;
  onViewModeChange?: (mode: AdminViewMode) => void;
  showViewToggle?: boolean;
  seoIssuesOnly?: boolean;
  onSeoIssuesOnlyChange?: (value: boolean) => void;
  showSeoFilter?: boolean;
  pageSize?: number;
  onPageSizeChange?: (value: number) => void;
  pageSizeOptions?: readonly number[];
  children?: React.ReactNode;
}

const DEFAULT_STATUS: AdminStatusOption[] = [
  { value: 'all', label: 'Všetky stavy' },
  { value: 'published', label: 'Publikované' },
  { value: 'draft', label: 'Koncept' },
  { value: 'archived', label: 'Archivované' },
];

export const DEFAULT_PAGE_SIZE_OPTIONS = [5, 10, 20, 50, 100] as const;

export const AdminListToolbar: React.FC<AdminListToolbarProps> = ({
  search,
  onSearchChange,
  searchPlaceholder = 'Hľadať…',
  statusFilter,
  onStatusFilterChange,
  statusOptions = DEFAULT_STATUS,
  viewMode,
  onViewModeChange,
  showViewToggle = false,
  seoIssuesOnly = false,
  onSeoIssuesOnlyChange,
  showSeoFilter = false,
  pageSize,
  onPageSizeChange,
  pageSizeOptions = DEFAULT_PAGE_SIZE_OPTIONS,
  children,
}) => (
  <div className="w-full space-y-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 p-3 sm:p-4">
    <div className="flex flex-col gap-3 md:flex-row md:items-center">
      <div className="flex-1 min-w-0">
        <input
          type="search"
          value={search}
          onChange={(e) => onSearchChange(e.target.value)}
          placeholder={searchPlaceholder}
          className="form-input w-full"
          aria-label={searchPlaceholder}
        />
      </div>

      {onStatusFilterChange && statusFilter !== undefined && (
        <select
          value={statusFilter}
          onChange={(e) => onStatusFilterChange(e.target.value)}
          className="form-input w-full md:w-auto md:min-w-[160px] shrink-0"
          aria-label="Filter stavu"
        >
          {statusOptions.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      )}
    </div>

    <div className="flex flex-col gap-3 xl:flex-row xl:flex-wrap xl:items-center">
      <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3 min-w-0">
        {onPageSizeChange && pageSize !== undefined && (
          <select
            value={pageSize}
            onChange={(e) => onPageSizeChange(Number(e.target.value))}
            className="form-input w-full sm:w-auto sm:min-w-[130px] shrink-0"
            aria-label="Počet položiek na stránku"
          >
            {pageSizeOptions.map((option) => (
              <option key={option} value={option}>
                {option} / stránku
              </option>
            ))}
          </select>
        )}

        {children && <div className="w-full sm:w-auto shrink-0">{children}</div>}
      </div>

      <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3 xl:ml-auto min-w-0">
        {showViewToggle && viewMode && onViewModeChange && (
          <AdminViewModeToggle mode={viewMode} onChange={onViewModeChange} className="w-full sm:w-auto" />
        )}

        {showSeoFilter && onSeoIssuesOnlyChange && (
          <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap shrink-0">
            <input
              type="checkbox"
              checked={seoIssuesOnly}
              onChange={(e) => onSeoIssuesOnlyChange(e.target.checked)}
              className="rounded border-gray-300"
            />
            Len SEO problémy
          </label>
        )}
      </div>
    </div>
  </div>
);

export default AdminListToolbar;
