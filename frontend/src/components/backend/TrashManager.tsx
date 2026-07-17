// frontend/src/components/backend/TrashManager.tsx
import React, { useCallback, useEffect, useState } from 'react';
import { ArchiveRestore, Loader2, Trash2 } from 'lucide-react';
import { trashApi, type TrashItem } from '../../api/trash';
import { useToast } from '../../hooks/useToast';

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
    // toast ref is stable for the component lifetime
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    void loadItems();
  }, [loadItems]);

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
                <th className="px-4 py-3 font-medium">Pôvodná cesta</th>
                <th className="px-4 py-3 font-medium">Zmazané</th>
                <th className="px-4 py-3 font-medium">Veľkosť</th>
                <th className="px-4 py-3 font-medium text-right">Akcia</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
              {items.map((item) => (
                <tr key={item.id} className="hover:bg-slate-50/80 dark:hover:bg-slate-800/30">
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
