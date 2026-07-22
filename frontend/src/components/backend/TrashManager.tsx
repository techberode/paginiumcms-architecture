// frontend/src/components/backend/TrashManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ArchiveRestore, Loader2, Trash2 } from 'lucide-react';
import { trashApi, type TrashItem } from '../../api/trash';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useAdminListQueryParams } from '../../hooks/useAdminListQueryParams';
import { SortableTableHeader } from './SortableTableHeader';
import { BulkActionBar } from './BulkActionBar';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { applyClientListView } from '../../utils/clientListView';
import { summarizeBulkResult } from '../../types/bulk';
import { useI18n } from '../../context/I18nContext';

function formatBytes(size: number): string {
  if (size < 1024) {
    return `${size} B`;
  }
  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`;
  }
  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDeletedAt(value: string, locale: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString(locale === 'en' ? 'en-US' : 'sk-SK');
}

export const TrashManager: React.FC = () => {
  const { t, locale } = useI18n();
  const [items, setItems] = useState<TrashItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [restoringId, setRestoringId] = useState<string | null>(null);
  const [bulkBusy, setBulkBusy] = useState(false);
  const {
    page,
    search,
    sortField,
    sortDirection,
    handleSort,
    setSearch,
    setPage,
    resetFilters,
  } = useAdminListQueryParams('deletedAt', 'desc');
  const [pageSize, setPageSize] = useAdminListPageSize('trash');
  const hasActiveFilters =
    search.trim().length >= 2 ||
    sortField !== 'deletedAt' ||
    sortDirection !== 'desc' ||
    page > 1;
  const toast = useToast();

  const loadItems = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await trashApi.list());
    } catch {
      toast.error(t('trash.toast.loadFailed'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [t]);

  useEffect(() => {
    void loadItems();
  }, [loadItems]);

  useEffect(() => {
    setPage(1);
  }, [pageSize, setPage]);

  const listView = useMemo(
    () =>
      applyClientListView(items, {
        search,
        searchText: (item) =>
          `${item.originalPath} ${item.filename} ${formatDeletedAt(item.deletedAt, locale)} ${formatBytes(item.size)}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'path', label: t('trash.sort.path'), getValue: (item) => item.originalPath },
          { value: 'deletedAt', label: t('trash.sort.deletedAt'), getValue: (item) => item.deletedAt },
          { value: 'size', label: t('trash.sort.size'), getValue: (item) => item.size },
        ],
        page,
        pageSize,
      }),
    [items, page, pageSize, search, sortDirection, sortField, locale, t]
  );

  const bulkSelection = useBulkSelection(
    listView.items.map((item) => item.id),
    `${page}:${search}:${sortField}:${sortDirection}:${pageSize}`
  );

  const handleRestore = async (item: TrashItem) => {
    if (!confirm(t('trash.confirm.restoreOne', { path: item.originalPath }))) {
      return;
    }

    setRestoringId(item.id);
    try {
      const result = await trashApi.restore(item.id);
      if (result) {
        toast.success(t('trash.toast.restored', { path: result.originalPath }));
        await loadItems();
      } else {
        toast.error(t('trash.toast.restoreFailed'));
      }
    } catch {
      toast.error(t('trash.toast.restoreFailed'));
    } finally {
      setRestoringId(null);
    }
  };

  const handleBulkRestore = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('trash.confirm.bulkRestore', { count: String(bulkSelection.count) }))) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await trashApi.bulkRestore(bulkSelection.selectedIds);
      if (result) {
        toast.success(summarizeBulkResult(result, t));
        bulkSelection.clear();
        await loadItems();
      } else {
        toast.error(t('trash.toast.bulkRestoreFailed'));
      }
    } finally {
      setBulkBusy(false);
    }
  };

  const handleBulkPurge = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(t('trash.confirm.bulkPurge', { count: String(bulkSelection.count) }))) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await trashApi.bulkPurge(bulkSelection.selectedIds);
      if (result) {
        toast.success(summarizeBulkResult(result, t));
        bulkSelection.clear();
        await loadItems();
      } else {
        toast.error(t('trash.toast.bulkPurgeFailed'));
      }
    } finally {
      setBulkBusy(false);
    }
  };

  const handleBulkBackup = async () => {
    if (bulkSelection.count === 0) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await trashApi.bulkBackup(bulkSelection.selectedIds);
      if (result) {
        toast.success(t('trash.toast.backupCreated', { count: String(result.count) }));
        const downloaded = await trashApi.downloadBackup(result.downloadUrl, result.filename);
        if (!downloaded) {
          toast.warning(t('trash.toast.backupDownloadFailed'));
        }
      } else {
        toast.error(t('trash.toast.backupFailed'));
      }
    } finally {
      setBulkBusy(false);
    }
  };

  const handleEmptyTrash = async () => {
    if (items.length === 0) {
      return;
    }
    if (!confirm(t('trash.confirm.empty', { count: String(items.length) }))) {
      return;
    }

    setBulkBusy(true);
    try {
      const removed = await trashApi.emptyTrash();
      if (removed !== null) {
        toast.success(
          removed > 0
            ? t('trash.toast.emptied', { count: String(removed) })
            : t('trash.toast.alreadyEmpty')
        );
        bulkSelection.clear();
        await loadItems();
      } else {
        toast.error(t('trash.toast.emptyFailed'));
      }
    } finally {
      setBulkBusy(false);
    }
  };

  return (
    <div className="p-6 w-full max-w-none mx-auto space-y-6">
      <div className="flex items-center justify-between gap-3 flex-wrap">
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
            <Trash2 className="w-5 h-5" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">{t('trash.page.title')}</h1>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              {t('trash.page.subtitle')}
            </p>
          </div>
        </div>
        <button
          type="button"
          className="btn btn-danger"
          disabled={bulkBusy || items.length === 0}
          onClick={() => void handleEmptyTrash()}
        >
          {t('trash.actions.empty')}
        </button>
      </div>

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder={t('trash.search.placeholder')}
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
        onResetFilters={resetFilters}
        showResetFilters={hasActiveFilters}
      />

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel={t('trash.bulk.itemLabel')}
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'restore',
            label: t('trash.bulk.restore'),
            variant: 'primary',
            disabled: bulkBusy,
            onClick: () => void handleBulkRestore(),
          },
          {
            id: 'backup',
            label: t('trash.bulk.backup'),
            variant: 'secondary',
            disabled: bulkBusy,
            onClick: () => void handleBulkBackup(),
          },
          {
            id: 'purge',
            label: t('trash.bulk.purge'),
            variant: 'danger',
            disabled: bulkBusy,
            onClick: () => void handleBulkPurge(),
          },
        ]}
      />

      {loading ? (
        <div className="flex items-center gap-2 text-slate-500 py-12 justify-center">
          <Loader2 className="w-5 h-5 animate-spin" />
          {t('trash.loading')}
        </div>
      ) : listView.total === 0 ? (
        <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-12 text-center text-slate-500">
          {items.length === 0 ? t('trash.empty.none') : t('trash.empty.filter')}
        </div>
      ) : (
        <>
          <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 w-full">
            <table className="w-full text-sm table-fixed sm:table-auto">
              <thead className="bg-slate-50 dark:bg-slate-800/50 text-left text-slate-600 dark:text-slate-400">
                <tr>
                  <th className="px-4 py-3 font-medium w-10">
                    <input
                      type="checkbox"
                      checked={bulkSelection.allSelected && listView.items.length > 0}
                      onChange={bulkSelection.toggleAll}
                      aria-label={t('trash.table.selectAll')}
                    />
                  </th>
                  <SortableTableHeader
                    label={t('trash.table.path')}
                    field="path"
                    activeField={sortField}
                    direction={sortDirection}
                    onSort={handleSort}
                    thClassName="px-4 py-3"
                  />
                  <SortableTableHeader
                    label={t('trash.table.deletedAt')}
                    field="deletedAt"
                    activeField={sortField}
                    direction={sortDirection}
                    onSort={handleSort}
                    thClassName="px-4 py-3 hide-mobile"
                  />
                  <SortableTableHeader
                    label={t('trash.table.size')}
                    field="size"
                    activeField={sortField}
                    direction={sortDirection}
                    onSort={handleSort}
                    thClassName="px-4 py-3"
                  />
                  <th className="px-4 py-3 font-medium text-right">{t('trash.table.action')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                {listView.items.map((item) => (
                  <tr key={item.id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
                    <td className="px-4 py-3">
                      <input
                        type="checkbox"
                        checked={bulkSelection.isSelected(item.id)}
                        onChange={() => bulkSelection.toggle(item.id)}
                        aria-label={t('trash.table.selectOne', { path: item.originalPath })}
                      />
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-200">
                      {item.originalPath}
                    </td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                      {formatDeletedAt(item.deletedAt, locale)}
                    </td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                      {formatBytes(item.size)}
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button
                        type="button"
                        onClick={() => void handleRestore(item)}
                        disabled={restoringId === item.id || bulkBusy}
                        className="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                      >
                        {restoringId === item.id ? (
                          <Loader2 className="w-3.5 h-3.5 animate-spin" />
                        ) : (
                          <ArchiveRestore className="w-3.5 h-3.5" />
                        )}
                        {t('trash.actions.restore')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <AdminListPagination
            page={listView.page}
            totalPages={listView.totalPages}
            total={listView.total}
            pageSize={pageSize}
            loading={loading}
            onPageChange={setPage}
            itemLabel={t('trash.pagination.itemLabel')}
          />
        </>
      )}
    </div>
  );
};
