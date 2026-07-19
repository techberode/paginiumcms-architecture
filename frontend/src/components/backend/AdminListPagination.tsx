import React from 'react';

export interface AdminListPaginationProps {
  page: number;
  totalPages: number;
  total: number;
  pageSize: number;
  loading?: boolean;
  onPageChange: (page: number) => void;
  itemLabel?: string;
}

export const AdminListPagination: React.FC<AdminListPaginationProps> = ({
  page,
  totalPages,
  total,
  pageSize,
  loading = false,
  onPageChange,
  itemLabel = 'záznamov',
}) => {
  if (total <= pageSize && totalPages <= 1) {
    return (
      <p className="text-sm text-gray-500 dark:text-gray-400">
        {total} {itemLabel}
      </p>
    );
  }

  return (
    <div className="flex items-center justify-between gap-4 flex-wrap">
      <p className="text-sm text-gray-500 dark:text-gray-400">
        {total} {itemLabel} · strana {page} / {totalPages}
      </p>
      <div className="flex gap-2 w-full sm:w-auto">
        <button
          type="button"
          className="btn btn-secondary text-sm flex-1 sm:flex-none"
          disabled={page <= 1 || loading}
          onClick={() => onPageChange(Math.max(1, page - 1))}
        >
          Predošlá
        </button>
        <button
          type="button"
          className="btn btn-secondary text-sm flex-1 sm:flex-none"
          disabled={page >= totalPages || loading}
          onClick={() => onPageChange(page + 1)}
        >
          Ďalšia
        </button>
      </div>
    </div>
  );
};

export default AdminListPagination;
