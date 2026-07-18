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
  children?: React.ReactNode;
}

const DEFAULT_STATUS: AdminStatusOption[] = [
  { value: 'all', label: 'Všetky stavy' },
  { value: 'published', label: 'Publikované' },
  { value: 'draft', label: 'Koncept' },
  { value: 'archived', label: 'Archivované' },
];

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
  children,
}) => (
  <div className="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
    <div className="flex flex-1 min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
      <div className="flex-1 min-w-[200px]">
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
          className="form-input w-full sm:w-auto sm:min-w-[160px]"
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

    <div className="flex flex-wrap items-center gap-3">
      {showViewToggle && viewMode && onViewModeChange && (
        <AdminViewModeToggle mode={viewMode} onChange={onViewModeChange} className="w-full sm:w-auto" />
      )}

      {showSeoFilter && onSeoIssuesOnlyChange && (
        <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
          <input
            type="checkbox"
            checked={seoIssuesOnly}
            onChange={(e) => onSeoIssuesOnlyChange(e.target.checked)}
            className="rounded border-gray-300"
          />
          Len SEO problémy
        </label>
      )}

      {children}
    </div>
  </div>
);

export default AdminListToolbar;
