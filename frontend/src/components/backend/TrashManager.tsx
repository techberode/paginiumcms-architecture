// frontend/src/components/backend/TrashManager.tsx
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ArchiveRestore, Loader2, Trash2 } from 'lucide-react';
import { trashApi, type TrashItem } from '../../api/trash';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { useAdminListPageSize } from '../../hooks/useAdminListPageSize';
import { useColumnSort } from '../../hooks/useColumnSort';
import { SortableTableHeader } from './SortableTableHeader';
import { BulkActionBar } from './BulkActionBar';
import { AdminListToolbar } from './AdminListToolbar';
import { AdminListPagination } from './AdminListPagination';
import { applyClientListView } from '../../utils/clientListView';
import { summarizeBulkResult } from '../../types/bulk';

function formatBytes(size: number): string {
  if (size < 1024) {
    return `${size} B`;
  }
  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(1)} KB`;
  }
  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDeletedAt(value: string): string {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleString('sk-SK');
}

export const TrashManager: React.FC = () => {
  const [items, setItems] = useState<TrashItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [restoringId, setRestoringId] = useState<string | null>(null);
  const [bulkBusy, setBulkBusy] = useState(false);
  const [search, setSearch] = useState('');
  const { sortField, sortDirection, handleSort } = useColumnSort('deletedAt', 'desc');
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useAdminListPageSize('trash');
  const toast = useToast();

  const loadItems = useCallback(async () => {
    setLoading(true);
    try {
      setItems(await trashApi.list());
    } catch {
      toast.error('Nepodarilo sa načítať kôš');
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    void loadItems();
  }, [loadItems]);

  useEffect(() => {
    setPage(1);
  }, [search, sortField, sortDirection, pageSize]);

  const listView = useMemo(
    () =>
      applyClientListView(items, {
        search,
        searchText: (item) =>
          `${item.originalPath} ${item.filename} ${formatDeletedAt(item.deletedAt)} ${formatBytes(item.size)}`,
        sortField,
        sortDirection,
        sortFields: [
          { value: 'path', label: 'Cesta', getValue: (item) => item.originalPath },
          { value: 'deletedAt', label: 'Dátum', getValue: (item) => item.deletedAt },
          { value: 'size', label: 'Veľkosť', getValue: (item) => item.size },
        ],
        page,
        pageSize,
      }),
    [items, page, pageSize, search, sortDirection, sortField]
  );

  const bulkSelection = useBulkSelection(
    listView.items.map((item) => item.id),
    `${page}:${search}:${sortField}:${sortDirection}:${pageSize}`
  );

  const handleRestore = async (item: TrashItem) => {
    if (!confirm(`Obnoviť „${item.originalPath}"?`)) {
      return;
    }

    setRestoringId(item.id);
    try {
      const result = await trashApi.restore(item.id);
      if (result) {
        toast.success(`Obnovené: ${result.originalPath}`);
        await loadItems();
      } else {
        toast.error('Obnova zlyhala');
      }
    } catch {
      toast.error('Obnova zlyhala');
    } finally {
      setRestoringId(null);
    }
  };

  const handleBulkRestore = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(`Obnoviť ${bulkSelection.count} položiek z koša?`)) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await trashApi.bulkRestore(bulkSelection.selectedIds);
      if (result) {
        toast.success(summarizeBulkResult(result));
        bulkSelection.clear();
        await loadItems();
      } else {
        toast.error('Hromadná obnova zlyhala');
      }
    } finally {
      setBulkBusy(false);
    }
  };

  const handleBulkPurge = async () => {
    if (bulkSelection.count === 0) {
      return;
    }
    if (!confirm(`Natrvalo zmazať ${bulkSelection.count} položiek? Túto akciu nemožno vrátiť.`)) {
      return;
    }

    setBulkBusy(true);
    try {
      const result = await trashApi.bulkPurge(bulkSelection.selectedIds);
      if (result) {
        toast.success(summarizeBulkResult(result));
        bulkSelection.clear();
        await loadItems();
      } else {
        toast.error('Trvalé mazanie zlyhalo');
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
        toast.success(`Záloha vytvorená (${result.count} položiek)`);
        const downloaded = await trashApi.downloadBackup(result.downloadUrl, result.filename);
        if (!downloaded) {
          toast.warning('Záloha bola vytvorená, ale stiahnutie zlyhalo.');
        }
      } else {
        toast.error('Záloha koša zlyhala');
      }
    } finally {
      setBulkBusy(false);
    }
  };

  const handleEmptyTrash = async () => {
    if (items.length === 0) {
      return;
    }
    if (!confirm(`Vysypať celý kôš (${items.length} položiek)? Položky sa natrvalo zmažú.`)) {
      return;
    }

    setBulkBusy(true);
    try {
      const removed = await trashApi.emptyTrash();
      if (removed !== null) {
        toast.success(removed > 0 ? `Kôš vyprázdnený (${removed} položiek)` : 'Kôš je prázdny');
        bulkSelection.clear();
        await loadItems();
      } else {
        toast.error('Vysypanie koša zlyhalo');
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
            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Kôš</h1>
            <p className="text-sm text-slate-500 dark:text-slate-400">
              Soft-delete obsah — obnova presunie súbor späť na pôvodné miesto.
            </p>
          </div>
        </div>
        <button
          type="button"
          className="btn btn-danger"
          disabled={bulkBusy || items.length === 0}
          onClick={() => void handleEmptyTrash()}
        >
          Vysypať kôš
        </button>
      </div>

      <AdminListToolbar
        search={search}
        onSearchChange={setSearch}
        searchPlaceholder="Hľadať podľa cesty, názvu alebo dátumu…"
        pageSize={pageSize}
        onPageSizeChange={setPageSize}
      />

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="položiek vybraných"
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'restore',
            label: 'Obnoviť',
            variant: 'primary',
            disabled: bulkBusy,
            onClick: () => void handleBulkRestore(),
          },
          {
            id: 'backup',
            label: 'Zálohovať',
            variant: 'secondary',
            disabled: bulkBusy,
            onClick: () => void handleBulkBackup(),
          },
          {
            id: 'purge',
            label: 'Zmazať natrvalo',
            variant: 'danger',
            disabled: bulkBusy,
            onClick: () => void handleBulkPurge(),
          },
        ]}
      />

      {loading ? (
        <div className="flex items-center gap-2 text-slate-500 py-12 justify-center">
          <Loader2 className="w-5 h-5 animate-spin" />
          Načítavam…
        </div>
      ) : listView.total === 0 ? (
        <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-12 text-center text-slate-500">
          {items.length === 0 ? 'Kôš je prázdny.' : 'Nenašli sa žiadne položky pre aktuálny filter.'}
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
                      aria-label="Vybrať všetko"
                    />
                  </th>
                  <SortableTableHeader
                    label="Pôvodná cesta"
                    field="path"
                    activeField={sortField}
                    direction={sortDirection}
                    onSort={handleSort}
                    thClassName="px-4 py-3"
                  />
                  <SortableTableHeader
                    label="Zmazané"
                    field="deletedAt"
                    activeField={sortField}
                    direction={sortDirection}
                    onSort={handleSort}
                    thClassName="px-4 py-3 hide-mobile"
                  />
                  <SortableTableHeader
                    label="Veľkosť"
                    field="size"
                    activeField={sortField}
                    direction={sortDirection}
                    onSort={handleSort}
                    thClassName="px-4 py-3"
                  />
                  <th className="px-4 py-3 font-medium text-right">Akcia</th>
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
                        aria-label={`Vybrať ${item.originalPath}`}
                      />
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-200">
                      {item.originalPath}
                    </td>
                    <td className="px-4 py-3 text-slate-600 dark:text-slate-400">
                      {formatDeletedAt(item.deletedAt)}
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
                        Obnoviť
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
            itemLabel="položiek"
          />
        </>
      )}
    </div>
  );
};
