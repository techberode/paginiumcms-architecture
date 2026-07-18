// frontend/src/components/backend/TrashManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { ArchiveRestore, Loader2, Trash2 } from 'lucide-react';
import { trashApi, type TrashItem } from '../../api/trash';
import { useToast } from '../../hooks/useToast';
import { useBulkSelection } from '../../hooks/useBulkSelection';
import { BulkActionBar } from './BulkActionBar';
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
  const [bulkRestoring, setBulkRestoring] = useState(false);
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

  const bulkSelection = useBulkSelection(
    items.map((item) => item.id),
    items.length
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

    setBulkRestoring(true);
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
      setBulkRestoring(false);
    }
  };

  return (
    <div className="p-6 max-w-5xl mx-auto space-y-6">
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

      <BulkActionBar
        count={bulkSelection.count}
        itemLabel="položiek vybraných"
        onClear={bulkSelection.clear}
        actions={[
          {
            id: 'restore',
            label: 'Obnoviť vybrané',
            variant: 'primary',
            disabled: bulkRestoring,
            onClick: () => void handleBulkRestore(),
          },
        ]}
      />

      {loading ? (
        <div className="flex items-center gap-2 text-slate-500 py-12 justify-center">
          <Loader2 className="w-5 h-5 animate-spin" />
          Načítavam…
        </div>
      ) : items.length === 0 ? (
        <div className="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-12 text-center text-slate-500">
          Kôš je prázdny.
        </div>
      ) : (
        <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 dark:bg-slate-800/50 text-left text-slate-600 dark:text-slate-400">
              <tr>
                <th className="px-4 py-3 font-medium w-10">
                  <input
                    type="checkbox"
                    checked={bulkSelection.allSelected && items.length > 0}
                    onChange={bulkSelection.toggleAll}
                    aria-label="Vybrať všetko"
                  />
                </th>
                <th className="px-4 py-3 font-medium">Pôvodná cesta</th>
                <th className="px-4 py-3 font-medium">Zmazané</th>
                <th className="px-4 py-3 font-medium">Veľkosť</th>
                <th className="px-4 py-3 font-medium text-right">Akcia</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {items.map((item) => (
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
                      disabled={restoringId === item.id}
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
      )}
    </div>
  );
};
