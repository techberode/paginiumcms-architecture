import React, { useMemo } from 'react';
import { AdminViewModeToggle } from './AdminViewModeToggle';
import type { AdminViewMode } from '../../hooks/useAdminViewMode';
import { useI18n } from '../../context/I18nContext';
import { DEFAULT_PAGE_SIZE_OPTIONS } from './adminListToolbarConstants';

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
  staleOnly?: boolean;
  onStaleOnlyChange?: (value: boolean) => void;
  showStaleFilter?: boolean;
  pageSize?: number;
  onPageSizeChange?: (value: number) => void;
  pageSizeOptions?: readonly number[];
  pageSizeInputMode?: 'select' | 'number';
  pageSizeMin?: number;
  pageSizeMax?: number;
  onResetFilters?: () => void;
  showResetFilters?: boolean;
  children?: React.ReactNode;
}

export const AdminListToolbar: React.FC<AdminListToolbarProps> = ({
  search,
  onSearchChange,
  searchPlaceholder,
  statusFilter,
  onStatusFilterChange,
  statusOptions,
  viewMode,
  onViewModeChange,
  showViewToggle = false,
  seoIssuesOnly = false,
  onSeoIssuesOnlyChange,
  showSeoFilter = false,
  staleOnly = false,
  onStaleOnlyChange,
  showStaleFilter = false,
  pageSize,
  onPageSizeChange,
  pageSizeOptions = DEFAULT_PAGE_SIZE_OPTIONS,
  pageSizeInputMode = 'select',
  pageSizeMin = 5,
  pageSizeMax = 100,
  onResetFilters,
  showResetFilters = false,
  children,
}) => {
  const { t } = useI18n();
  const resolvedSearchPlaceholder = searchPlaceholder ?? t('list.toolbar.searchPlaceholder');
  const resolvedStatusOptions = useMemo(
    () =>
      statusOptions ?? [
        { value: 'all', label: t('list.status.all') },
        { value: 'published', label: t('list.status.published') },
        { value: 'draft', label: t('list.status.draft') },
        { value: 'archived', label: t('list.status.archived') },
        { value: 'scheduled', label: t('list.status.scheduled') },
      ],
    [statusOptions, t]
  );

  return (
    <div className="w-full space-y-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 p-3 sm:p-4">
      <div className="flex flex-col gap-3 md:flex-row md:items-center">
        <div className="flex-1 min-w-0">
          <input
            type="search"
            value={search}
            onChange={(e) => onSearchChange(e.target.value)}
            placeholder={resolvedSearchPlaceholder}
            className="form-input w-full"
            aria-label={resolvedSearchPlaceholder}
          />
        </div>

        {onStatusFilterChange && statusFilter !== undefined && (
          <select
            value={statusFilter}
            onChange={(e) => onStatusFilterChange(e.target.value)}
            className="form-input w-full md:w-auto md:min-w-[160px] shrink-0"
            aria-label={t('list.toolbar.statusFilterAria')}
          >
            {resolvedStatusOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>
        )}
      </div>

      <div className="flex flex-col gap-3 xl:flex-row xl:flex-wrap xl:items-center">
        <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:gap-3 min-w-0">
          {onPageSizeChange && pageSize !== undefined && pageSizeInputMode === 'number' && (
            <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 shrink-0">
              <span className="whitespace-nowrap">{t('list.toolbar.pageSizeAria')}</span>
              <input
                type="number"
                min={pageSizeMin}
                max={pageSizeMax}
                value={pageSize}
                onChange={(e) => {
                  const parsed = Number(e.target.value);
                  if (Number.isFinite(parsed)) {
                    onPageSizeChange(Math.max(pageSizeMin, Math.min(pageSizeMax, parsed)));
                  }
                }}
                className="form-input w-24"
                aria-label={t('list.toolbar.pageSizeAria')}
              />
            </label>
          )}

          {onPageSizeChange && pageSize !== undefined && pageSizeInputMode === 'select' && (
            <select
              value={pageSize}
              onChange={(e) => onPageSizeChange(Number(e.target.value))}
              className="form-input w-full sm:w-auto sm:min-w-[130px] shrink-0"
              aria-label={t('list.toolbar.pageSizeAria')}
            >
              {pageSizeOptions.map((option) => (
                <option key={option} value={option}>
                  {t('list.toolbar.perPage', { count: option })}
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
              {t('list.toolbar.seoIssuesOnly')}
            </label>
          )}

          {showStaleFilter && onStaleOnlyChange && (
            <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap shrink-0">
              <input
                type="checkbox"
                checked={staleOnly}
                onChange={(e) => onStaleOnlyChange(e.target.checked)}
                className="rounded border-gray-300"
              />
              {t('list.toolbar.staleOnly')}
            </label>
          )}

          {showResetFilters && onResetFilters && (
            <button
              type="button"
              onClick={onResetFilters}
              className="btn btn-secondary text-xs px-3 py-1.5 shrink-0"
            >
              {t('list.toolbar.clearFilters')}
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminListToolbar;
